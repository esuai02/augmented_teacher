<?php
/**
 * Event Bus Implementation
 * Publisher-Subscriber pattern for event-driven architecture
 * 
 * @package ALT42\Events
 * @version 1.0.0
 */

namespace ALT42\Events;

/**
 * Event Bus for managing event publication and subscription
 */
class EventBus {
    /** @var array */
    private $subscribers = [];
    /** @var array */
    private $middleware = [];
    /** @var array */
    private $eventQueue = [];
    /** @var array */
    private $responseCache = [];
    /** @var bool */
    private $asyncEnabled = true;
    
    /**
     * Publish an event to all subscribers
     * 
     * @param string $topic Event topic
     * @param array $data Event data
     * @param int $priority Event priority (1-10)
     */
    public function publish(string $topic, array $data, int $priority = 5): void {
        // Create event message
        $message = [
            'topic' => $topic,
            'data' => $data,
            'priority' => $priority,
            'timestamp' => microtime(true),
            'event_id' => $data['event_id'] ?? uniqid('evt_')
        ];
        
        // Apply middleware
        foreach ($this->middleware as $mw) {
            $message = $mw->process($message);
            if ($message === null) {
                return; // Middleware blocked the event
            }
        }
        
        // High priority - process immediately
        if ($priority >= 8) {
            $this->processImmediate($message);
        } else {
            // Queue for async processing
            $this->queueEvent($message);
            if ($this->asyncEnabled) {
                $this->processQueuedEvents();
            }
        }
    }
    
    /**
     * Subscribe to an event topic
     * 
     * @param string $topic Event topic or pattern
     * @param callable $handler Event handler
     * @param array $options Subscription options
     */
    public function subscribe(string $topic, callable $handler, array $options = []): string {
        $subscription_id = uniqid('sub_');
        
        if (!isset($this->subscribers[$topic])) {
            $this->subscribers[$topic] = [];
        }
        
        $this->subscribers[$topic][$subscription_id] = [
            'handler' => $handler,
            'options' => $options,
            'created_at' => time()
        ];
        
        return $subscription_id;
    }
    
    /**
     * Unsubscribe from an event
     * 
     * @param string $topic Event topic
     * @param string $subscription_id Subscription ID
     */
    public function unsubscribe(string $topic, string $subscription_id): bool {
        if (isset($this->subscribers[$topic][$subscription_id])) {
            unset($this->subscribers[$topic][$subscription_id]);
            return true;
        }
        return false;
    }
    
    /**
     * Add middleware to the event pipeline
     * 
     * @param EventMiddleware $middleware Middleware instance
     */
    public function addMiddleware(EventMiddleware $middleware): void {
        $this->middleware[] = $middleware;
    }
    
    /**
     * Process event immediately (synchronous)
     */
    private function processImmediate(array $message): void {
        $topic = $message['topic'];
        $responses = [];
        
        // Find matching subscribers
        $handlers = $this->findMatchingHandlers($topic);
        
        foreach ($handlers as $handler_info) {
            try {
                $response = call_user_func($handler_info['handler'], $message);
                $responses[] = $response;
            } catch (\Exception $e) {
                error_log("Event handler error for topic {$topic}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
                $responses[] = [
                    'error' => true,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        // Cache responses for synchronous events
        if (!empty($responses)) {
            $this->responseCache[$message['event_id']] = $responses;
        }
    }
    
    /**
     * Queue event for async processing
     */
    private function queueEvent(array $message): void {
        // Priority queue implementation
        $priority = $message['priority'];
        
        if (!isset($this->eventQueue[$priority])) {
            $this->eventQueue[$priority] = [];
        }
        
        $this->eventQueue[$priority][] = $message;
        
        // Sort by priority (descending)
        krsort($this->eventQueue);
    }
    
    /**
     * Process queued events asynchronously
     */
    private function processQueuedEvents(): void {
        foreach ($this->eventQueue as $priority => $events) {
            foreach ($events as $key => $message) {
                $this->processImmediate($message);
                unset($this->eventQueue[$priority][$key]);
            }
            
            if (empty($this->eventQueue[$priority])) {
                unset($this->eventQueue[$priority]);
            }
        }
    }
    
    /**
     * Find handlers matching the topic pattern
     */
    private function findMatchingHandlers(string $topic): array {
        $handlers = [];
        
        foreach ($this->subscribers as $pattern => $subs) {
            // Check exact match or wildcard pattern
            if ($pattern === $topic || $this->matchesPattern($topic, $pattern)) {
                foreach ($subs as $sub) {
                    $handlers[] = $sub;
                }
            }
        }
        
        return $handlers;
    }
    
    /**
     * Check if topic matches pattern (supports wildcards)
     */
    private function matchesPattern(string $topic, string $pattern): bool {
        // Convert pattern to regex
        $regex = str_replace(
            ['*', '?'],
            ['.*', '.'],
            preg_quote($pattern, '/')
        );
        
        return preg_match('/^' . $regex . '$/', $topic) === 1;
    }
    
    /**
     * Wait for response from synchronous event
     * 
     * @param string $event_id Event ID
     * @param int $timeout_ms Timeout in milliseconds
     * @return array|null Response data or null if timeout
     */
    public function waitForResponse(string $event_id, int $timeout_ms = 5000): ?array {
        $start_time = microtime(true) * 1000;
        
        while ((microtime(true) * 1000 - $start_time) < $timeout_ms) {
            if (isset($this->responseCache[$event_id])) {
                $response = $this->responseCache[$event_id];
                unset($this->responseCache[$event_id]);
                return $response;
            }
            
            // Sleep for 10ms before checking again
            usleep(10000);
        }
        
        return null; // Timeout
    }
    
    /**
     * Get event queue status
     */
    public function getQueueStatus(): array {
        $status = [
            'queue_depth' => 0,
            'by_priority' => []
        ];
        
        foreach ($this->eventQueue as $priority => $events) {
            $count = count($events);
            $status['queue_depth'] += $count;
            $status['by_priority'][$priority] = $count;
        }
        
        return $status;
    }
    
    /**
     * Enable or disable async processing
     */
    public function setAsyncEnabled(bool $enabled): void {
        $this->asyncEnabled = $enabled;
    }
}

/**
 * Interface for event middleware
 */
interface EventMiddleware {
    /**
     * Process event message
     * Return null to block the event
     * 
     * @param array $message Event message
     * @return array|null Processed message or null to block
     */
    public function process(array $message): ?array;
}

/**
 * Logging middleware implementation
 */
class LoggingMiddleware implements EventMiddleware {
    /** @var string */
    private $logFile;

    public function __construct(string $logFile = '/tmp/alt42_events.log') {
        $this->logFile = $logFile;
    }
    
    public function process(array $message): ?array {
        $log_entry = sprintf(
            "[%s] Event: %s, Priority: %d, ID: %s\n",
            date('Y-m-d H:i:s'),
            $message['topic'],
            $message['priority'],
            $message['event_id']
        );
        
        error_log($log_entry, 3, $this->logFile);
        
        return $message;
    }
}

/**
 * Validation middleware implementation
 */
class ValidationMiddleware implements EventMiddleware {
    /** @var array */
    private $requiredFields = ['topic', 'data', 'priority'];

    public function process(array $message): ?array {
        foreach ($this->requiredFields as $field) {
            if (!isset($message[$field])) {
                error_log("Event validation failed: missing field {$field} at " . __FILE__ . ":" . __LINE__);
                return null;
            }
        }
        
        // Validate priority range
        if ($message['priority'] < 1 || $message['priority'] > 10) {
            error_log("Event validation failed: priority out of range at " . __FILE__ . ":" . __LINE__);
            return null;
        }
        
        return $message;
    }
}

