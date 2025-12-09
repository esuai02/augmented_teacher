#!/usr/bin/env python3
"""
Claude Code Subagents CLI (Python version)

Command-line interface for browsing and using subagents

Usage:
    python subagent_cli.py list                          - List all subagents
    python subagent_cli.py show <category> <agent>       - Show specific subagent
    python subagent_cli.py recommend <task>              - Get recommendations for a task
    python subagent_cli.py search <keyword>              - Search subagents by keyword
"""

import os
import sys
import json
import glob
from pathlib import Path

# ANSI color codes
COLOR_GREEN = "\033[0;32m"
COLOR_BLUE = "\033[0;34m"
COLOR_YELLOW = "\033[0;33m"
COLOR_RED = "\033[0;31m"
COLOR_RESET = "\033[0m"

class SubagentManager:
    def __init__(self):
        self.base_path = Path(__file__).parent / 'awesome-claude-code-subagents-main' / 'categories'
        self.categories = {
            '01-core-development': 'Core Development',
            '02-language-specialists': 'Language Specialists',
            '03-infrastructure': 'Infrastructure',
            '04-quality-security': 'Quality & Security',
            '05-data-ai': 'Data & AI',
            '06-developer-experience': 'Developer Experience',
            '07-specialized-domains': 'Specialized Domains',
            '08-business-product': 'Business & Product',
            '09-meta-orchestration': 'Meta Orchestration',
            '10-research-analysis': 'Research & Analysis'
        }
        
    def list_subagents(self):
        """List all available subagents"""
        subagents = {}
        
        for category in self.categories.keys():
            category_path = self.base_path / category
            if category_path.exists():
                md_files = list(category_path.glob('*.md'))
                if md_files:
                    subagents[category] = [f.stem for f in md_files]
                    
        return subagents
    
    def parse_frontmatter(self, content):
        """Parse YAML frontmatter from markdown file"""
        metadata = {}
        
        if content.startswith('---'):
            parts = content.split('---', 2)
            if len(parts) >= 3:
                yaml_content = parts[1]
                for line in yaml_content.strip().split('\n'):
                    line = line.strip()
                    if ':' in line:
                        key, value = line.split(':', 1)
                        metadata[key.strip()] = value.strip()
                        
        return metadata
    
    def get_subagent(self, category, agent_name):
        """Get a specific subagent definition"""
        file_path = self.base_path / category / f"{agent_name}.md"
        
        if not file_path.exists():
            return None
            
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        metadata = self.parse_frontmatter(content)
        
        # Extract prompt (everything after frontmatter)
        parts = content.split('---', 2)
        prompt = parts[2].strip() if len(parts) >= 3 else ''
        
        return {
            'name': metadata.get('name', agent_name),
            'description': metadata.get('description', ''),
            'tools': metadata.get('tools', ''),
            'prompt': prompt,
            'category': self.categories.get(category, category),
            'file': str(file_path)
        }
    
    def get_recommendations(self, task_type):
        """Get recommended subagents for specific tasks"""
        recommendations = {
            'api': [
                ('01-core-development', 'api-designer'),
                ('01-core-development', 'backend-developer'),
                ('01-core-development', 'graphql-architect')
            ],
            'frontend': [
                ('01-core-development', 'frontend-developer'),
                ('01-core-development', 'ui-designer'),
                ('02-language-specialists', 'react-specialist'),
                ('02-language-specialists', 'vue-expert')
            ],
            'database': [
                ('03-infrastructure', 'database-administrator'),
                ('02-language-specialists', 'sql-pro')
            ],
            'security': [
                ('04-quality-security', 'security-expert'),
                ('04-quality-security', 'penetration-tester')
            ],
            'testing': [
                ('04-quality-security', 'test-engineer'),
                ('04-quality-security', 'qa-specialist')
            ],
            'performance': [
                ('04-quality-security', 'performance-engineer'),
                ('03-infrastructure', 'database-administrator')
            ],
            'documentation': [
                ('06-developer-experience', 'documentation-writer'),
                ('06-developer-experience', 'api-documenter')
            ],
            'php': [
                ('02-language-specialists', 'php-pro'),
                ('02-language-specialists', 'laravel-specialist')
            ],
            'python': [
                ('02-language-specialists', 'python-pro'),
                ('02-language-specialists', 'django-developer')
            ],
            'moodle': [
                ('07-specialized-domains', 'education-specialist'),
                ('01-core-development', 'backend-developer')
            ]
        }
        
        return recommendations.get(task_type, [])

class SubagentCLI:
    def __init__(self):
        self.manager = SubagentManager()
        
    def show_help(self):
        """Show help message"""
        print(f"{COLOR_BLUE}=== Claude Code Subagents CLI ==={COLOR_RESET}")
        print("\nUsage:")
        print("  python subagent_cli.py list                    - List all available subagents")
        print("  python subagent_cli.py show <category> <agent> - Show details of a specific subagent")
        print("  python subagent_cli.py recommend <task>        - Get recommendations for a task type")
        print("  python subagent_cli.py search <keyword>        - Search subagents by keyword")
        print("  python subagent_cli.py help                    - Show this help message")
        print("\nTask types for recommendations:")
        print("  api, frontend, database, security, testing, performance, documentation, php, python, moodle")
        print("\nExample:")
        print("  python subagent_cli.py show 01-core-development backend-developer")
        print("  python subagent_cli.py recommend api")
        
    def list_subagents(self):
        """List all available subagents"""
        print(f"{COLOR_BLUE}=== Available Subagents ==={COLOR_RESET}")
        
        subagents = self.manager.list_subagents()
        
        for category, agents in subagents.items():
            print(f"\n{COLOR_GREEN}[{category}]{COLOR_RESET}")
            for agent in agents:
                print(f"  • {agent}")
                
        print(f"\n{COLOR_YELLOW}Total categories: {len(subagents)}{COLOR_RESET}")
        total_agents = sum(len(agents) for agents in subagents.values())
        print(f"{COLOR_YELLOW}Total subagents: {total_agents}{COLOR_RESET}")
        
    def show_subagent(self, category, agent_name):
        """Show details of a specific subagent"""
        agent = self.manager.get_subagent(category, agent_name)
        
        if not agent:
            print(f"{COLOR_RED}Error: Subagent '{agent_name}' not found in category '{category}'{COLOR_RESET}")
            return
            
        print(f"{COLOR_BLUE}=== Subagent Details ==={COLOR_RESET}")
        print(f"{COLOR_GREEN}Name: {COLOR_RESET}{agent['name']}")
        print(f"{COLOR_GREEN}Category: {COLOR_RESET}{agent['category']}")
        print(f"{COLOR_GREEN}Description: {COLOR_RESET}{agent['description']}")
        print(f"{COLOR_GREEN}Tools: {COLOR_RESET}{agent['tools']}")
        print(f"{COLOR_GREEN}File: {COLOR_RESET}{agent['file']}")
        print(f"\n{COLOR_BLUE}=== Prompt (first 1000 chars) ==={COLOR_RESET}")
        print(agent['prompt'][:1000])
        
        if len(agent['prompt']) > 1000:
            print(f"{COLOR_YELLOW}\n... (truncated, full prompt is {len(agent['prompt'])} characters){COLOR_RESET}")
            
    def recommend_subagents(self, task_type):
        """Get recommendations for a task type"""
        print(f"{COLOR_BLUE}=== Recommendations for '{task_type}' ==={COLOR_RESET}")
        
        recommendations = self.manager.get_recommendations(task_type)
        
        if not recommendations:
            print(f"{COLOR_YELLOW}No specific recommendations found for '{task_type}'.{COLOR_RESET}")
            print("Try one of these task types: api, frontend, database, security, testing, performance, documentation, php, python, moodle")
            return
            
        for i, (category, agent_name) in enumerate(recommendations, 1):
            agent = self.manager.get_subagent(category, agent_name)
            if agent:
                print(f"\n{COLOR_GREEN}{i}. {agent['name']}{COLOR_RESET}")
                print(f"   Category: {category}")
                print(f"   Description: {agent['description']}")
                print(f"   Tools: {agent['tools']}")
                print(f"   Command: python subagent_cli.py show {category} {agent_name}")
                
    def search_subagents(self, keyword):
        """Search subagents by keyword"""
        print(f"{COLOR_BLUE}=== Searching for '{keyword}' ==={COLOR_RESET}")
        
        keyword_lower = keyword.lower()
        results = []
        subagents = self.manager.list_subagents()
        
        for category, agents in subagents.items():
            for agent_name in agents:
                agent = self.manager.get_subagent(category, agent_name)
                if agent:
                    # Search in name, description, and tools
                    search_text = f"{agent['name']} {agent['description']} {agent['tools']}".lower()
                    if keyword_lower in search_text:
                        results.append({
                            'category': category,
                            'name': agent_name,
                            'agent': agent
                        })
                        
        if not results:
            print(f"{COLOR_YELLOW}No subagents found matching '{keyword}'.{COLOR_RESET}")
            return
            
        print(f"{COLOR_GREEN}Found {len(results)} matching subagent(s):{COLOR_RESET}")
        
        for result in results:
            print(f"\n• {COLOR_GREEN}{result['agent']['name']}{COLOR_RESET} [{result['category']}]")
            print(f"  {result['agent']['description']}")
            print(f"  Command: python subagent_cli.py show {result['category']} {result['name']}")
            
    def run(self, args):
        """Run the CLI with given arguments"""
        if len(args) < 2:
            self.show_help()
            return
            
        command = args[1]
        
        if command == 'list':
            self.list_subagents()
        elif command == 'show':
            if len(args) < 4:
                print(f"{COLOR_RED}Error: Please provide category and agent name{COLOR_RESET}")
                print("Usage: python subagent_cli.py show <category> <agent>")
                return
            self.show_subagent(args[2], args[3])
        elif command == 'recommend':
            if len(args) < 3:
                print(f"{COLOR_RED}Error: Please provide task type{COLOR_RESET}")
                print("Available task types: api, frontend, database, security, testing, performance, documentation, php, python, moodle")
                return
            self.recommend_subagents(args[2])
        elif command == 'search':
            if len(args) < 3:
                print(f"{COLOR_RED}Error: Please provide search keyword{COLOR_RESET}")
                return
            self.search_subagents(args[2])
        elif command == 'help':
            self.show_help()
        else:
            print(f"{COLOR_RED}Unknown command: {command}{COLOR_RESET}")
            self.show_help()

if __name__ == '__main__':
    cli = SubagentCLI()
    cli.run(sys.argv)