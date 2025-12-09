좋습니다. JSON 기반 온톨로지와 MySQL을 사용하고, GPT API를 통해 멀티턴 대화 속에서 온톨로지를 보정하며, 교사가 직접 입력하고 다양한 형태의 콘텐츠 카드를 출력하는 시스템을 설계하는 전략을 조사하겠습니다. 프론트엔드와 백엔드는 PHP, JS, HTML, CSS만 사용하는 조건도 반영하여 안전하고 확장 가능한 구조를 중심으로 설계하겠습니다.

조사 완료 후 단계별 아키텍처와 코딩 전략, GPT 프롬프트 구조, DB 설계 등을 포함한 결과를 알려드리겠습니다.


# Designing a GPT-Assisted Educational Chatbot with Ontology and Content Cards

## 1. System Overview

This system is an **educational chatbot for math class** that uses a teacher-defined ontology of situations and responses to guide student interactions. The chatbot can interpret a student’s statement (e.g. *“I can’t solve this problem, I give up.”*), identify the underlying situation (e.g. *“student is frustrated and losing confidence”*), and respond with an appropriate strategy. The response includes **interactive content cards** (like hints, easier problems, or video lessons) to re-engage the student. The system also features a **multi-turn loop** where GPT-4 (via the OpenAI API) not only helps generate helpful responses but also **analyzes and refines the ontology** over time. The entire application is built using **PHP (backend)** with a **MySQL database** (storing ontology and logs), and a **web front-end (HTML/CSS/JS)** for the chat interface and teacher input forms. JSON is used as the primary data format for the ontology and content cards, enabling easy manipulation and feeding of structured data into GPT. The design emphasizes **robustness and incremental development**: building the system in clear stages to avoid getting lost in complexity, and minimizing GPT token usage by providing only relevant context in prompts. The ultimate goal is a maintainable, precise system that could be handed off to a coding agent or developer to implement seamlessly.

## 2. Key Requirements and Design Principles

* **Ontology-Driven Responses:** Teacher-defined “situation → strategy” mappings form an ontology (stored in JSON). The chatbot’s logic is grounded in this ontology, ensuring that responses align with pedagogical strategies provided by teachers.
* **Teacher Input & Editing:** Teachers can easily input new situations, causes, and response strategies (with content card references) through a user-friendly interface. These inputs update the ontology stored in MySQL (in JSON format) in real time.
* **Content Cards for Engagement:** The system can present various types of content cards (text explanations, images, videos, links to exercises, etc.) as part of the chatbot’s answer. These cards are intended to encourage student interaction (e.g. a “Try this easier problem” button or a “Watch this concept video” link). The design supports **arbitrary content types** by using a flexible JSON structure for content data.
* **GPT Integration:** Use OpenAI’s GPT API to enhance the chatbot’s capabilities in two ways: (1) **Natural Language Understanding & Response Generation** – GPT helps map student input to the appropriate ontology entry and/or phrases the response in an encouraging, student-friendly manner; (2) **Ontology Refinement** – GPT can analyze where the ontology might be lacking or too coarse and suggest precise improvements (in small JSON patches) in a multi-turn loop.
* **Minimal Token Usage:** The system is optimized to **feed GPT only the necessary information** (e.g. the relevant part of the ontology and the user query) to minimize token consumption. This involves prompt engineering and possibly using short summaries or keys for situations rather than dumping the entire ontology. Refinement suggestions from GPT are targeted and concise to conserve tokens. (This aligns with research indicating that *refining prompts and optimizing how much ontology context is provided leads to better results*.)
* **Stability via Stepwise Development:** The architecture is divided into clear modules and steps (data storage, teacher interface, chat logic, GPT calls, refinement loop, UI enhancements). Each component can be developed and tested independently before integration. This step-by-step approach ensures that as complexity grows, the developer does not lose track of the system’s behavior. Robust validation and error-handling at each stage prevent the system from breaking due to GPT quirks or code bugs.
* **Technology Stack Constraints:** Use **PHP** for server-side logic (handling API calls, database operations, and implementing the chatbot logic). Use **MySQL** to store persistent data (ontology JSON, content records, conversation logs, user data). Use **HTML/CSS** for structure and styling and **JavaScript** for front-end interactivity (e.g. updating the chat interface dynamically and handling content card actions). No external frameworks or languages beyond these are assumed, which keeps the deployment simple and compatible with typical web servers.

*(Throughout this design, we will delve into each component and decision in detail, ensuring that the resulting specification is comprehensive and ready for implementation.)*

## 3. Ontology Data Structure (JSON in MySQL)

A clear data model for the ontology is critical. We choose **JSON** as the format to represent the ontology because it is human-readable, easy to manipulate in code, and can be directly passed to GPT in prompts. The ontology captures **situations (scenarios)** and their associated **causes** and **response strategies**. An example structure for one situation might look like:

```json
{
  "situation": "학생이 문제를 풀지 못하고 포기하려고 함",  
  "causes": ["자신감 부족", "개념 이해 부족"],  
  "strategies": [  
    {  
      "strategy": "개념 보충 설명",  
      "content": { "type": "video", "title": "핵심 개념 정리 영상", "url": "https://.../concept_review.mp4" }  
    },  
    {  
      "strategy": "성공 경험 제공",  
      "content": { "type": "exercise", "title": "쉬운 연습문제", "id": "EZ-Prob-01" }  
    }  
  ]  
}
```

In this JSON example:

* **situation:** Describes the scenario in natural language (in Korean, since this is a Korean classroom scenario).
* **causes:** A list of possible root causes or reasons (e.g. lack of confidence, insufficient understanding). These help classify the student’s issue.
* **strategies:** A list of response strategies for this situation. Each strategy entry can have a description (“strategy”) and an associated **content card** (“content”). The content is an object that can vary in structure depending on its type. For instance, a video content card might have a URL, whereas an exercise card might have an `id` that the system uses to fetch a problem from a database. We allow this flexibility by storing content details as a JSON object.

**Storage in MySQL:** We have options for storing this ontology:

* *Single JSON blob:* We could have a single table (e.g. `Ontology`) with a single row that stores the entire ontology as one JSON text or a MySQL JSON column. This is simple (only one place to update/read), and we can fetch the whole ontology or sections of it easily. However, as the ontology grows, pulling the entire JSON for every query might be heavy.
* *Structured tables:* Alternatively, design separate tables (`Situations`, `Strategies`, `Contents`) and use foreign keys. For example, `Situations(id, description)`, `Causes(situation_id, cause)`, `Strategies(id, situation_id, strategy_text)`, `ContentCards(strategy_id, type, title, url/id, etc)`. This is more relational and ensures consistency (especially if multiple situations reuse the same content). If using this approach, the system would assemble JSON on the fly for GPT or keep a cached JSON representation.
* *Hybrid:* A combination where each situation is stored as a JSON object in a table (e.g. `Situations` table with columns: `id`, `situation_text`, `situation_json`). The JSON might contain the causes and strategies as shown. MySQL’s JSON data type allows indexing into the JSON if needed.

For simplicity and direct GPT usage, we can store each situation’s JSON in a table. For example, a `situations` table with columns: `id (INT)`, `situation_text (VARCHAR)`, `data (JSON)`. The `data` column holds the JSON blob (as above) for that situation. This way, we can retrieve just the relevant situation’s JSON when a student query matches a situation (minimizing data to send to GPT). We also maintain a separate table for content card details if needed (especially if content cards are reused or need additional metadata).

**Example Table Structure:**

* `Situation`: **id** (PK), **description** (text of the situation), **causes (JSON)**, **strategies (JSON)**.
* `ContentCard`: **id** (PK), **type**, **title**, **url** (or **content**), etc. (If content details are embedded in the `strategies` JSON, this table might be optional. But having it can be useful for listing all content or linking to actual content files in a CMS.)
* Optionally, `Teacher` table for teacher accounts (if multiple teachers input data), and `Logs` for storing conversation logs or GPT suggestions for auditing.

Using JSON in the database allows the application to query sub-keys if needed (MySQL supports JSON queries), and it keeps the structure flexible. This is important because the ontology may evolve: teachers could add new fields (e.g. a “difficulty” rating for content, or a “grade level”), and storing as JSON means the schema can adapt without altering the DB tables every time.

**Maintaining consistency:** We will enforce some structure in the JSON through the application logic (for example, always require that a strategy has a `content` with known fields). We might also implement a simple JSON schema or validation in PHP when saving teacher input, to catch errors early (ensuring that ontology errors are minimized upfront).

## 4. Teacher Input and Ontology Management Interface

To keep the ontology updated and relevant, **teachers (or administrators)** need an easy way to add and modify situations and their responses. We design a **web-based interface** for ontology management:

* **Input Form:** A teacher-facing form (HTML/JS) allows entering a new scenario. For example:

  * A text field for “Situation description” (e.g. *“학생이 문제를 풀지 못하고 포기하려 함”*).
  * A multi-select or checklist for causes (or a free text that gets split into cause tags). Initially, the teacher can type causes like "자신감 부족" etc. We might provide some common cause suggestions to maintain consistency.
  * For each response strategy, inputs for “Strategy description” and an associated “Content Card”. The content card input might dynamically change based on type: if teacher selects type “video”, show a field for URL; if “exercise”, maybe show a dropdown of existing easy problem IDs or allow input; if “text”, provide a text area for the content, etc. This can be implemented simply with JavaScript to hide/show fields.
  * The interface should allow adding multiple strategies for one situation (e.g. clicking “Add another strategy” button creates another set of strategy+content fields).
* **Editing Existing Entries:** The interface can list existing situations in a table or list. The teacher can select one to edit. On edit, the form populates with current data (fetched from the JSON in DB) and allows changes.
* **Data Submission:** When the teacher submits the form (for a new situation or an edit), a PHP script processes it:

  * Validate the inputs (e.g., ensure required fields are not empty, JSON structure is maintained, etc.).
  * Convert the input into the JSON structure for the situation.
  * If new, insert into the `situations` table. If editing, update the corresponding row’s JSON.
  * Possibly log the change (especially if we want to track ontology evolution or have an undo/version mechanism).
* **User Experience Considerations:** Keep the form straightforward. We might use some JavaScript to assist (like a bit of Vue.js or jQuery to manage dynamic fields, given only JS is allowed). However, even without heavy frameworks, simple show/hide and template cloning for multiple strategies can be done with vanilla JS.
* **Preventing Errors:** Since ontology correctness is crucial, the form should prevent known errors:

  * If the teacher leaves a field blank that is required, prompt them.
  * If the JSON assembly fails (e.g. some bracket mismatch – unlikely if we build it programmatically in PHP), handle that gracefully.
  * Potentially provide a “preview JSON” button so the teacher can see the structured data they are adding.
  * If using select inputs for content types or causes, it avoids typos (e.g. consistently using “자신감 부족” vs “자신감 부족함” – which might otherwise be two separate cause labels).
* **Authorization:** Only authenticated teacher/admin users should access this interface. Ensure a login (could be a simple PHP session login) to prevent random users from altering the ontology. This is outside the core logic but important for real deployment.

By giving teachers direct control, the system stays up-to-date with classroom needs. The **teacher interface is a critical component** that must be intuitive; if it’s too complex, the ontology will not grow. Therefore, we also consider offering an **import/export** option: e.g., teachers might prepare a JSON or CSV offline and upload it to populate multiple scenarios at once. This can expedite initial setup or bulk edits.

## 5. Content Cards and Resource Management

Content cards are central to encouraging student interaction. The system should support **arbitrary types of content cards**, meaning it’s not limited to one kind of resource. Here’s how we handle content cards:

* **Content Card Structure:** In the ontology JSON, each strategy includes a `content` object. The `content` has a **`type`** field which determines how the front-end will render it, and other fields depending on the type. We define some standard content types, for example:

  * `video`: For video resources – expects fields like `url` (link to the video file or YouTube), `title` (display name).
  * `exercise`: For practice problem – expects an `id` or reference (which the system can use to fetch the problem statement from a database or a predefined set), and possibly a `title` or difficulty.
  * `text`: A simple textual tip or explanation – could have a `text` field with the content (and maybe `title` if needed).
  * `image`: An image card – expects an image `url` and a caption or `title`.
  * `link`: a generic link to an external resource or article – with `url` and `title`.
  * Others as needed (the system can be extended to new content types easily by following the same pattern).
* **Storage of Content:** Small content (like a short text hint) can be stored directly in the JSON. Larger content (like a video file or a detailed exercise) might reside externally:

  * If an exercise has an ID, the exercise details could be stored in another table (or fetched from an existing learning management system). The chatbot could fetch those details on the fly when the card is presented (for example, via an AJAX call when the student clicks “Show Hint”).
  * Videos could be hosted on a platform or server – the card just contains the URL/ID.
  * We should maintain a **Content repository** (maybe a MySQL table `content_cards` or similar) listing all content items with their metadata. This can help teachers reuse content: e.g., when adding a strategy, the form might allow choosing an existing content item or creating a new one.
* **Presenting Content Cards in Chat:** On the front-end, when the chatbot sends a response containing a content card, the UI should render it in an attractive, interactive way:

  * For example, if the content type is `video`, the chat message might show a thumbnail or a play button with the title; clicking it could open a video modal or new window.
  * If it’s an `exercise`, maybe a snippet of the problem is shown with a “Solve this” button. That button could lead to a problem-solving interface or just display the problem and an input for the student to try answer.
  * For a `text` card, simply show the text in a highlighted manner (like a tip).
  * We can achieve this with conditional rendering in JavaScript: e.g., the chat message comes with data like `{type: 'video', title: '...', url: '...'}`, and the JS code constructs the appropriate HTML (maybe using templates).
* **Multiple Cards:** Sometimes more than one content card might be relevant (e.g. one strategy could involve two resources). The ontology could allow an array of contents per strategy. Alternatively, we treat each strategy-content as one card and possibly send multiple strategy suggestions. To keep it simple: one strategy yields one content card in our design, but the chatbot could present multiple strategies if needed (like “Option 1: watch a video, Option 2: try an easier problem”).
* **Arbitrary Content Types:** Because the content is stored as flexible JSON, adding a new type in the future (say a “quiz” or an “interactive widget”) won’t require a schema change – just front-end logic to handle that type. The teacher interface can provide a generic option to input key-value pairs for unknown types or we update the form when adding new types.
* **Example:** Suppose a teacher wants to add a “real-world example” content type in future. They could still do it by selecting “link” type for now or just placing a description, but if we later formalize a `realworld` type with fields like `description` and `link`, the JSON might have `"type": "realworld", "description": "How this math applies in daily life", "url": "some_article_link"}`, and front-end would need an update to render it properly (like maybe an icon and the description). The backend and DB are already compatible due to JSON flexibility.

In summary, content cards are handled in a modular way: *store the data in JSON (with references to external content as needed), and handle each type on the front-end for display.* This ensures the chatbot’s recommendations are not just text but actionable items for the student, increasing engagement.

## 6. Student Chatbot Interaction Flow

Now we outline how a typical **student-chatbot conversation** works, from the moment a student sends a message to delivering a response with content cards. The flow involves both deterministic logic (ontology lookup) and GPT-based intelligence for understanding and phrasing:

1. **Student Message Reception:** A student types a message in the chat UI (for example: *“이 문제 너무 어려워요. 포기할래요.” which means "This problem is too difficult, I want to give up."*). When they hit send, an AJAX call (or WebSocket message) sends this text to a PHP backend endpoint (e.g., `chat.php` with an action=message and session ID).
2. **Message Processing & Situation Matching:** The backend needs to determine which *situation* in the ontology this message corresponds to. This can be done in a couple of ways:

   * **Rule/Keyword Matching:** We could do a simple check for keywords like “포기” (give up), “어려워” (difficult) etc., mapping to known situations. For instance, if “포기” appears and it's a math context, map to *“학생이 문제 풀이를 포기하려 함”* situation. This method is fast and uses zero tokens, but requires maintaining a mapping and might be brittle for varied phrasings.
   * **Semantic Search or Embeddings:** Convert the student message to an embedding vector and compare with embeddings of each situation description (precomputed). The closest match is the likely situation. This requires an embedding model but could be done offline (OpenAI or local model) and avoids a full GPT call for classification.
   * **GPT-based Classification:** Use a small GPT prompt to classify the message. E.g., prompt: *“Ontologies: {list of situation names}. Which situation best fits the user message: '{message}'?”*. However, sending the list of all situations every time might be token-expensive if there are many. A variant is to ask GPT to extract keywords or emotion first and then map with a small rule.
   * **Chosen Approach:** Initially, implement a simple deterministic or embedding-based classification to avoid heavy token usage. This is in line with our minimal token principle. We can refine this with GPT if needed for ambiguous cases, but often student utterances in this context may be predictable enough for simple rules. (The ontology causes can guide keywords: e.g., “자신감 부족” cause might correlate with phrases like "못 하겠어요", "포기", "어려워".)
3. **Retrieve Relevant Ontology Entry:** Once a situation is identified (say `situation_id = 5` corresponding to “low confidence, wants to give up”), the system fetches that situation’s JSON from MySQL. Now we have a structured list of possible strategies and content for this scenario.
4. **Select or Formulate a Response Strategy:** The system decides which strategy (or strategies) to present:

   * If there are multiple strategies for the situation, we might choose one based on context or at random for variety. For example, strategy A = “provide concept review” vs strategy B = “provide easier problem”. The choice could depend on any available context (if we tracked that the student already saw a video earlier, maybe now offer an exercise, etc.). Initially, a simple approach is to just take the first or cycle through them on subsequent occurrences.
   * We prepare the content card data associated with the chosen strategy. E.g., select the *“성공 경험 제공”* strategy which has an easy problem content card.
5. **Drafting the Chatbot Reply:** Now the chatbot’s textual reply is composed. There are two sub-parts:

   * **The textual encouragement or guidance:** We can craft this by combining static template text with ontology info, or use GPT to generate it. For example, a template might be: *"문제가 너무 어렵게 느껴질 수 있어요. 대신 **\${content.title}**(을)를 한 번 풀어보는 건 어때요? 조금 더 쉬운 문제예요."* (Translation: "I know the problem feels very hard. How about trying **\${content.title}**? It's a bit easier."). This template includes the content card’s title (“쉬운 연습문제”) in bold. Templates ensure consistency and zero token cost. But they may be rigid.
   * **Using GPT for phrasing:** We can call GPT with a prompt that includes the situation, chosen strategy, and content info, instructing it to produce a friendly response. For instance:

     ```
     System prompt: "You are a supportive math teaching assistant."
     User prompt: "Student says: '이 문제 너무 어려워요. 포기할래요.' The relevant situation is '학생이 문제 풀이를 포기함 (원인: 자신감 부족)'. The chosen strategy is '성공 경험 제공' with content '쉬운 연습문제'."
     Assistant task: "Encourage the student to try the easier problem and not give up, in Korean."
     ```

     GPT might return: *"지금 풀고 있는 문제가 많이 어려웠죠? 괜찮아요, 너무 걱정 말아요. 대신 조금 더 쉬운 문제 하나 풀어볼까요? 작은 성공을 통해 자신감을 다시 키워보는 거예요!"*. This dynamic generation can be more engaging. We must however weigh the token usage. A compromise is to only send minimal info. In fact, since we already have the strategy text and content, a templated or semi-templated approach might suffice. We might start with static or simple concatenation and only use GPT if the response needs to be more nuanced (or perhaps as a fall-back if no strategy fits well).
6. **Including Content Card in Reply:** The backend will send the response text along with the content card data to the front-end. For example, the JSON response from `chat.php` might be:

   ```json
   {
     "text": "괜찮아요, 너무 걱정 말고 대신 이 문제를 풀어볼까요? 작은 성공을 통해 자신감을 키워봐요!",
     "content": {
       "type": "exercise",
       "title": "쉬운 연습문제",
       "id": "EZ-Prob-01"
     }
   }
   ```

   The front-end JS receives this and adds a chat bubble for the assistant with that text, and renders the content card (e.g. a clickable card saying “쉬운 연습문제” that when clicked, shows the problem or navigates to a problem page).
7. **Student Interaction with Content:** If the student clicks the content card, depending on type, different things happen:

   * For a video, perhaps a video player pops up or a link opens.
   * For an exercise, possibly the problem statement is shown and the chatbot can await the student’s attempt. The design of that part depends on the larger context of the learning platform (it might integrate with a quiz system or just display the answer after a try).
   * This is part of the “interactive” aspect – it’s not just a one-turn chat, but a mini-activity.
8. **Follow-up Turns:** After the student engages with the content, the conversation can continue. They might say, “I solved it!” or “This is still confusing.” The chatbot should then handle that next turn:

   * If the student reports success, the bot can congratulate and maybe suggest returning to the original problem to try again.
   * If the student is still confused, perhaps the bot offers another strategy or escalates (maybe the next strategy was a concept review video, etc.).
   * **Maintaining Context:** We need to keep track of which situation we are in and what was offered. We can store the last situation ID and strategy used in the session (e.g., in a PHP session or an in-memory store). This way, the next student message can be interpreted in context. For instance, if the student says “Still don’t get it”, we know what “it” refers to (the easier problem or the concept) and can respond accordingly (maybe now definitely use the concept video strategy).
   * We must also be cautious to reset or shift context when a new, unrelated query comes. If the student starts a different topic, we should detect that (perhaps via keyword or explicit new question).
9. **Logging and Analysis:** Every Q\&A (student query + system response + chosen ontology path) can be logged in the database. This is useful for two reasons: (a) to analyze later how the system is used and where it fails, and (b) for the **ontology refinement process**, which will use these logs to see if a student query didn’t match well or if a strategy was ineffective.

This flow ensures that the chatbot provides **tailored help** based on known teaching strategies, while also using GPT as needed for natural language understanding and generation. The key is balancing **deterministic logic (for reliability and cost-effectiveness)** with **AI-powered flexibility** (for understanding and eloquence). Next, we address how GPT can help refine the knowledge base in a feedback loop.

## 7. GPT-Assisted Multi-Turn Ontology Refinement Loop

One innovative aspect of this system is the ability for GPT to **critique and improve the ontology itself** over multiple turns. This is essentially a learning loop where the AI identifies gaps or errors in the ontology after observing a conversation, and suggests updates. Here’s how we can implement this safely and efficiently:

* **Triggering the Feedback Loop:** After a conversation (or a specific interaction) with a student, the system triggers a review. This could be:

  * Automatically after certain types of exchanges (e.g., if the student’s query didn’t cleanly match any known situation, or if the conversation didn’t lead to resolution).
  * On-demand by a teacher: perhaps the teacher can press a “Analyze last conversation” button to get suggestions for ontology improvement.
  * Scheduled maintenance: the system could periodically sample logged interactions and run analysis.
* **GPT Analysis Prompt:** We prepare a prompt for GPT that includes:

  * A brief summary of the situation and the system’s response. For example: *“Student said: '...'. The system identified this as situation X (with causes Y) and responded with strategy Z and content card Q.”*
  * The relevant snippet of the ontology (the JSON for that situation, or if no situation matched well, maybe a note that “no exact situation was found, closest was X”). We do **not** send the entire ontology to GPT – just the part believed to be relevant.
  * Ask GPT to analyze the adequacy of that ontology entry. For instance: *“Based on the student’s statement, does the ontology’s classification seem appropriate? Is there a cause or scenario missing? Suggest improvements or new entries if necessary.”* We explicitly instruct GPT to be concise and output in a structured way (perhaps JSON or bullet points of suggestions).
* **Example GPT Response (Analysis):** GPT might respond with something like:

  * *Analysis:* “The student’s statement indicates not just lack of confidence, but also a possible **dislike for math** (‘수학은 진짜 체질이 아닌 것 같아요’ implies subject aversion). The ontology entry focused on confidence but didn’t cover *math aversion* as a cause.”
  * *Suggestion:* “Consider adding a cause for *subject aversion* and a strategy like *relating math to real-life interests* or *providing a fun activity*.”
    We might have prompted GPT to return a JSON patch. For example:

  ```json
  {
    "suggested_update": {
      "situation": "학생이 수학 자체를 싫어함",
      "causes": ["수학에 대한 거부감"],
      "strategies": [
        {
          "strategy": "수학의 재미 보여주기",
          "content": { "type": "video", "title": "재미있는 수학 이야기 영상", "url": "https://.../fun_math.mp4" }
        }
      ]
    },
    "existing_situation_to_modify": 5  // referencing the ID where this should be linked, or null if new
  }
  ```

  This indicates GPT thinks a new situation related to “dislike of math” should be added (or an existing situation expanded).
* **Verification and Application:** We **do not blindly trust** GPT’s suggestion to modify the ontology. Instead:

  * If possible, route this suggestion to a teacher or admin interface for approval. The suggestion can be displayed clearly (in natural language or as a diff: e.g., “Add new cause '수학에 대한 거부감' to situation 5, and a new strategy '수학의 재미 보여주기' with a fun math video.”). The teacher can confirm and perhaps tweak the wording or content.
  * Alternatively, implement automatic rules: e.g., if GPT suggests a completely new situation, perhaps it’s safe to add as a draft (marked as “unverified”) that doesn’t get used until approved. Or if it’s adding a cause to an existing situation, we might accept it if it clearly aligns with the conversation.
  * The system could also run validation on the suggestion: ensure the JSON structure is correct, required fields present, no duplicate keys, etc. (This can be done with a small PHP function or JSON schema check).
  * Once verified, the update is applied: e.g., insert a new situation row in the DB or update the JSON of an existing one accordingly.
* **Iterative Improvement:** Over time, this loop allows the ontology to grow more comprehensive:

  * New corner-case student behaviors get incorporated.
  * If GPT finds the phrasing of a situation or strategy confusing, it might suggest clarifications in the ontology text (which could help future GPT calls or teacher understanding).
  * Essentially, GPT acts as a **knowledge engineer assistant**, pointing out where the knowledge base could be refined.
* **Minimizing Token Use in Refinement:** We keep GPT’s role focused:

  * Only supply the relevant subset of data (situation JSON, student utterance, response strategy) rather than the entire knowledge base.
  * Use short, specific prompts and ask for **structured outputs** (as shown). This reduces verbose explanations from GPT and focuses on actionable items.
  * Possibly use GPT-3.5 for this analysis if that’s sufficient, since it’s cheaper and faster, and only escalate to GPT-4 for more complex judgments.
  * Limit the frequency of these operations (maybe not every single message, but when patterns emerge or an unknown scenario is hit).
* **Logging Changes:** Every change to the ontology from this loop should be logged (who/what suggested it, when applied). This helps track how the ontology evolves and provides traceability in case a GPT-suggested change turns out to be wrong, we can roll it back.

This multi-turn refinement mechanism is powerful but must be handled carefully. By keeping a human in the loop (for oversight) and enforcing structure, we mitigate the risk of GPT introducing errors. The result is a system that **learns from interactions**, gradually approaching a “complete” model of student help strategies defined by teachers, with far less manual labor than maintaining the ontology alone.

## 8. Strategies to Minimize GPT Token Usage

Using GPT API is resource-intensive, so optimizing prompts and context size is crucial for stability and cost. We incorporate several strategies in this design to **minimize token usage** while still leveraging GPT where it adds most value:

* **Selective Context Inclusion:** We will not send the entire ontology or conversation history to GPT each turn. Instead, we:

  * Extract key relevant pieces: e.g., the identified situation description and related info for a response generation prompt. This keeps the prompt concise.
  * Use short identifiers or summaries: For instance, instead of sending a full list of causes from the ontology to GPT when classifying, we might just send the situation titles or an abstract code (like “Situation5: student losing confidence”).
  * Example: *Instead of:* “Here is the JSON of the situation: {...full JSON...}”, *we send:* “Situation = 'losing confidence, giving up'; Cause= 'low confidence'; Strategy = 'offer easier problem'.”
* **Prompt Templates and Reuse:** By developing a good prompt for a given task, we can reuse it with minimal variations. For example, a template for response phrasing that we fill in with the specifics (student statement, chosen strategy). The system prompt (role) can remain constant (e.g. always instruct GPT to be a supportive tutor), and only a small user prompt with the variables is sent each time.
* **Local Processing First:** Attempt as much logic as possible without GPT:

  * Matching student input to a situation can often be done with a simple approach as discussed (regex, keywords, or embedding similarity) without invoking GPT classification every time.
  * The initial version of the bot might even have hard-coded responses or templates that work for most typical inputs; GPT can then be a fallback for unusual phrasing or cases not covered.
  * This way, GPT calls are only made when needed (e.g., if the system cannot confidently map an input or if we want a more nuanced reply).
* **Short GPT Outputs:** When using GPT for suggestions or analysis, we explicitly request short outputs:

  * For ontology refinement, ask for just the changes or a single JSON object, not a lengthy explanation. (We saw an example where GPT could output a small JSON patch.)
  * For student-facing responses, limit to a few sentences. In Korean especially, we can instruct GPT to be brief and encouraging. This not only saves tokens but also aligns with good tutoring practice (short, clear guidance).
* **Model Selection:** Use appropriate models for different tasks:

  * If OpenAI’s models are available, use `gpt-3.5-turbo` for straightforward tasks like classification or routine phrasing, and reserve `gpt-4` for complex tasks like analyzing the ontology or handling very ambiguous student queries. This isn’t exactly token reduction, but cost reduction and speed improvement.
  * In the future, if local LLMs or smaller models can run on the server, those could handle some tasks without hitting the API at all.
* **Throttling and Caching:**

  * Throttle how often GPT is called per user to avoid token spikes (e.g., if a student spams messages, maybe handle some with a generic “I’m thinking” message or a fixed response until previous ones processed).
  * Cache GPT results where possible. For instance, if classification of a particular phrase was done once, store that mapping so next time it’s instant. Similarly, if GPT created a nicely phrased response for a known situation, we can save that as a template for reuse.
* **Context Truncation in Multi-Turn Conversations:** If maintaining some conversation memory with GPT (to keep style or recall what student said earlier), ensure we summarize older turns instead of resending full text every time. A short summary of what the student is trying to do can accompany the prompt rather than a long back-and-forth history.
* **Validate Need for GPT:** Always ask “Can this be handled with rules or data lookup?” before using GPT. For example, if a student asks a direct factual question (like “What’s the formula for area of a circle?”), the system might retrieve that from a knowledge base or a fixed answer store rather than asking GPT, which saves tokens and ensures accuracy.

By combining these practices, the design ensures GPT’s power is utilized **judiciously**. This keeps the system cost-effective and fast, and also reduces the risk of GPT going off-track (as shorter, focused prompts are easier to control). As one study suggests, *refining how we feed context to the LLM and validating its outputs are key to reliable performance* – we incorporate those ideas here by streamlining context and validating GPT’s contributions at each step.

## 9. Step-by-Step Implementation Plan

To ensure we can build this complex system without losing track, we break the implementation into clear, manageable stages. Each stage produces a working subset of functionality that can be tested and verified before moving on.

**Step 1: Define the Data Schema**

* Draft the JSON structure for ontology entries (as done in section 3). Write a few example situations to refine the format. Ensure it covers all data we need (situation text, causes, strategies, content details).
* Set up the MySQL database:

  * Create tables `situations` (and possibly `content_cards` if using separate table). Use sample data to test inserting and querying JSON fields.
  * Verify that the JSON can be retrieved correctly via PHP (using MySQL’s JSON functions or treating it as text).
  * If needed, create an index on `situation.description` or other fields for faster search.

**Step 2: Implement Teacher Ontology Input Module**

* Create a basic HTML page with a form that allows adding a new situation. Initially, keep it simple: fields for situation text, one cause, one strategy, one content. (We will expand to multiple causes/strategies later once basic submission works.)
* Write a PHP script (e.g., `save_situation.php`) to handle the form submission:

  * Validate input.
  * Construct a JSON object (or prepare an SQL insert if storing parts in columns).
  * Insert into the DB.
  * Return success/failure.
* Test adding a few entries via the form and ensure they appear in the DB correctly.
* Expand the form: add JavaScript to allow multiple causes and strategies. This might involve using a bit of JS to clone input fields or using a `<template>` in HTML. Ensure the PHP can handle an array of causes/strategies from the POST data (e.g., using naming conventions like `cause[]` for multiple inputs).
* Implement an interface (maybe on the same page or a separate one) to list existing situations:

  * A simple table that queries all situations and displays their description and maybe causes.
  * Each row has an “Edit” button. Clicking it populates the form with the existing data (this can be done by an AJAX call to fetch the JSON, then filling the form fields, or by generating the form server-side).
  * Editing submission updates the DB row instead of inserting new.
* Test editing and verify changes in DB.
* Security: Add login/auth checks for these pages (this can be as simple as a hardcoded password check in PHP for now).

**Step 3: Basic Chatbot Backend (Rule-Based to Start)**

* Set up a chat front-end page (`chat.html` for instance) with a chat window UI:

  * Use HTML/CSS to create a chat transcript area and an input box for the student’s message.
  * Add JavaScript to handle sending the message to the backend (via `fetch` or AJAX) and appending the student message to the UI.
* On the backend, create `chat.php` to handle incoming messages:

  * Accept parameters: maybe `session_id` (to track conversation) and `message` text.
  * Implement a simple matching function in PHP: e.g., if message contains certain keywords or matches a regex, assign a situation ID. This could be a series of `if` statements or a small lookup array (e.g., `if (strpos($msg, "포기")!==false) $situation=5;` etc.). Use the sample ontology data to define these triggers.
  * If a match is found, retrieve that situation’s JSON from DB.
  * Pick the first strategy for now.
  * Formulate a response text. At this initial stage, **do not call GPT**. Instead, use a template string or even a very simple response like: `"선생님: " . $strategy_text . " - " . $content_title`.
  * Respond with a JSON containing the text and content info (as in section 6).
* Have the front-end JS parse this JSON response and display the bot’s message and a content card. For now, content card can be shown as a simple link or button (implement one type, like if `type == 'exercise'`, just show a button “Solve problem” that alerts “(Pretend problem given)” for testing).
* Test this flow end-to-end: type a message that should trigger a known situation and see if the correct response appears with the card.
* This step ensures the basic communication and logic loop is working without introducing GPT complexity yet.

**Step 4: Integrate GPT for Response Generation**

* Now that the basic pipeline works, incorporate GPT API calls in the backend to enhance responses:

  * Decide on using an OpenAI PHP client or simply use `curl` calls to the OpenAI REST API. (In PHP, one can use `file_get_contents` with a stream context, or cURL, to POST to `https://api.openai.com/v1/chat/completions` with the appropriate headers and JSON payload.)
  * Start with GPT for **response phrasing**. Implement a function `generateResponseText($situation, $strategy, $content)`:

    * This will prepare a prompt with the situation description, strategy, and content info, instructing GPT to output a single response sentence or two.
    * Example system message: *“You are a tutor chatbot helping a student. The student’s issue is: \${situation}. Your strategy: \${strategy}. Content to suggest: \${content.title}. Respond in a supportive tone in Korean.”* Then user message to GPT might be the student’s exact utterance for context.
    * Call the GPT API (probably with `gpt-4` for best results in Korean, or gpt-3.5 if cost is a concern). Parse the JSON result to extract the assistant’s reply text.
    * Use error handling: if the API fails or returns something unusable, fall back to a default phrase or a template.
  * Replace the earlier static response composition with this GPT-generated text. Concatenate or format it with the content card mention if needed (or instruct GPT to include something like <<CARD>> placeholder where the card is referenced).
  * Test with a few sample inputs. Check that GPT’s output is appropriate and that special characters or formatting (like quotes) are handled properly in JSON.
  * **Note:** Keep the prompt and instructions short to minimize tokens. We might not send the entire situation JSON, just a summary string.
* Optionally, integrate GPT for **situation matching** as well if needed:

  * If the rule-based matching is too naive, implement a small GPT prompt like: *“Possible situations: \[A, B, C]. Which fits: '...student message...'? Respond with the situation label only.”* This can run if the rule-based logic fails to find a match above a certain confidence.
  * Or use embeddings via OpenAI Embedding API. But since we want to stick to PHP, we might skip that or call OpenAI’s embedding endpoint similarly and do a cosine similarity in PHP.
* With GPT integrated, do more end-to-end tests:

  * Try various student inputs, including ones that are slightly different from the ontology phrasings, to see if GPT mapping works.
  * Ensure the response time is acceptable (maybe use streaming if needed, but that complicates the front-end; initially, a short delay is fine).
  * Tune the prompt if responses are not ideal (too long, off-tone, etc.). This is an iterative prompt-engineering process.

**Step 5: Front-End Content Card Enhancements**

* Improve how content cards are displayed and behave:

  * Use CSS to style the card (e.g. a bordered box with an icon depending on type: a play icon for videos, question mark for exercise, etc.).
  * If it’s a video link, maybe clicking opens an embedded video player (if we have a direct video URL, we can use an HTML5 `<video>` or an iframe for YouTube).
  * If it’s an exercise, clicking could open a modal showing the problem description. For this, we need to have the exercise content. Perhaps in the ontology JSON or via another AJAX call to get the problem by ID. For simplicity, we might store a short problem text in the ontology itself for the prototype.
  * Ensure that the card click events are handled by JS. If multiple cards or multiple strategies are ever sent, handle layout (e.g. stack them vertically).
  * Make sure the UI is mobile-friendly (students might be on tablets).
  * Test each content type display with sample data.

**Step 6: Multi-Turn Conversation Handling**

* Implement basic context management:

  * Assign each chat session a unique ID (maybe create one when the chat page loads and store in JS/PHP session). Use this to group messages.
  * In `chat.php`, maintain a simple state per session (could be in a PHP $\_SESSION or a temporary file/DB entry). For example, store last situation ID and last strategy used for that session.
  * On each new message, if the message is a follow-up (not a totally new question), we might check the last situation context. We can detect follow-ups if they come within a short time or if the student message is something like "I still don't get it" (which has no math terms, etc.). For now, a heuristic: if message length is short or contains words like “still, yet, didn’t understand”, treat it as follow-up.
  * If it’s a follow-up, we either continue with the same situation or escalate:

    * If already offered an easier problem and student is still stuck, maybe now offer the concept explanation strategy from the same situation.
    * If student succeeded, maybe close the loop by encouraging to try the original problem again or ask if they need more help.
  * Implement logic to fetch a new strategy if needed. Possibly mark the previous strategy as “used” in this session to avoid immediate repetition.
  * Append conversation memory for GPT if using it for response phrasing: we might send the last exchange as context (or a summary like "the student tried the easier problem and said: '...'").
  * Test by simulating a short conversation: student says “don’t get it”, bot responds with strategy 1, student then says “I still can’t do it”, bot responds with strategy 2, etc. Adjust logic for smoothness.

**Step 7: GPT-Assisted Ontology Refinement Integration**

* Build the mechanism to utilize GPT for ontology feedback:

  * Create a PHP script (maybe `review_chat.php` or integrate into an admin panel) that can be triggered after a conversation or for a specific message.
  * This script will gather the needed info: student message, the situation matched, the response given (maybe even the outcome if known).
  * Form the GPT prompt as designed in section 7, and call the GPT API (likely GPT-4 for best analysis) to get suggestions.
  * Parse GPT’s output. If we expect JSON, decode it. Validate the structure (e.g. ensure keys expected are present).
  * Store this suggestion in a temporary table or file, or immediately apply it depending on strategy:

    * A safe approach: store in a table `ontology_suggestions` with fields (suggested\_json, status, original\_situation\_id, timestamp, etc.).
    * Then notify an admin interface that there’s a suggestion. (Could even email the teacher or just show on their dashboard.)
  * Create an admin view to list pending suggestions. Allow the teacher to review and click “Apply” or “Dismiss”. If apply, the backend will either insert a new situation or update an existing one as per the suggestion.
  * If fully automating without manual approval (not recommended initially), ensure comprehensive validation. Possibly run the suggestion again through a check (like ask GPT: “Is this suggestion consistent and not duplicating existing entries?” but that again uses tokens).
* Test the refinement loop with a known scenario:

  * Deliberately remove something from ontology and simulate a conversation where that missing piece is needed. See if GPT suggests it. E.g., remove the “math aversion” cause, input a message implying that, and see if GPT suggests adding it.
  * Check the suggestion doesn’t break JSON format and that applying it yields a correct ontology state.

**Step 8: End-to-End Testing and Iteration**

* At this stage, test the entire system as a whole:

  * Have a fake “student” go through a variety of issues, ensure the chatbot responds appropriately each time and content cards work.
  * Have a “teacher” update some data (maybe add a new strategy), then have the student immediately benefit from it.
  * Test resilience: what if the student says something completely unexpected (not math related)? The bot should handle gracefully (maybe say it can’t help with that, or just provide a general encouragement). We might implement a catch-all GPT fallback: if no situation matches, call GPT with a prompt: *“Student said X, respond supportively saying you’re not sure how to help with that in math context.”*
  * If GPT fails or returns an error, ensure the system doesn’t crash. Handle timeouts by maybe returning a message: “(Thinking...)” and then a follow-up or a generic response.
  * Check performance: with the steps we have, each turn has maybe one or two DB queries and one GPT call (in worst case). This should be okay. If any step is slow, consider optimizations (like indexing, or simplifying prompts).
* Refine the implementation based on testing:

  * Adjust prompt wording, adjust threshold for matching vs GPT involvement, fix any UI bugs.
  * Add more content or refine existing ones to see how the system scales.

**Step 9: Deployment Considerations**

* Once the system is working in a dev environment, consider how it will run in production:

  * Secure the API keys and endpoints. The GPT API key should be kept server-side (in a config file not exposed to front-end).
  * Optimize front-end bundling (minify CSS/JS) and ensure compatibility (especially if using any modern JS features, ensure it works on school’s typical browser setups).
  * Plan for scaling: if many students use it simultaneously, make sure the PHP endpoints can handle concurrent requests (PHP itself is stateless per request; using session locking could be a bottleneck if not handled carefully, maybe use database for session storage or minimal session usage).
  * Logging and monitoring: use the logs to monitor if the bot is failing to classify certain queries often (maybe we need to add those situations) or if GPT errors are happening.

By following this step-by-step plan, we **build complexity gradually**. At each step, we have a working system:

* After step 3, a simple rule-based chatbot.
* After step 4, a fluent GPT-powered responder.
* After step 6, a multi-turn capable tutor.
* After step 7, a self-improving knowledge base.

This modular approach ensures we can pinpoint issues quickly (since we test after each increment) and avoid losing the design’s thread in a tangle of code. If one component becomes complex, we can refactor that in isolation. The result is a solid foundation ready for a coding agent or developer to translate into a “perfect” working system.

## 10. Ensuring System Stability and Robustness

Building an AI-driven educational tool requires careful attention to stability, given the unpredictability of both student input and AI output. Here are additional measures in the design to ensure the system remains **robust and safe**:

* **Robust Validation:** Any content coming from outside (teacher input or GPT suggestions) is validated:

  * Teacher inputs are checked (e.g., no malicious scripts in text, ensure required fields, etc.).
  * GPT JSON suggestions are run through a JSON parser and maybe a lightweight schema checker. If GPT returns text instead of JSON when JSON is expected, we discard or log it rather than applying blindly.
  * We incorporate the idea of *“robust validation mechanisms to ensure accuracy and trustworthiness”* of AI-generated results by double-checking before altering the knowledge base or showing content to students.
* **Controlled GPT Outputs:** We use system-level instructions in prompts to prevent undesired output. For example, always instruct GPT to remain positive, avoid any sensitive topics, and stick to the educational context. This minimizes the chance of it giving inappropriate advice. Also, by not giving it free rein (we structure its input and ask specific things), we reduce off-topic tangents.
* **Error Handling and Fallbacks:** If any part of the system fails:

  * **GPT API fails:** e.g., network error or rate limit – the bot should catch the exception and perhaps respond with a generic helpful message like “힘들어하는 것 같구나. 잠시만, 다시 한번 생각해볼래요?” (“It seems you’re having a hard time. Let’s take a moment and try again.”) which doesn’t rely on the ontology. And/or inform the student that it will get back to them. This prevents the chat from just hanging.
  * **No Situation Matched:** If the student’s input doesn’t match any known situation and even GPT classification is unsure, the bot can respond with a gentle prompt for clarification: *“Can you explain more about what you find difficult?”* This buys time and possibly yields keywords that do match.
  * **Database Errors:** All DB calls should be in try-catch blocks (or the PHP equivalent). If the ontology fetch fails, log it and respond with a generic apology and maybe suggest the student ask the teacher directly. The system should never expose raw errors to the user.
* **Logging and Monitoring:** We keep logs of:

  * Conversations (for debugging unexpected bot behavior).
  * GPT queries and responses (for tuning prompts and checking for any disallowed content).
  * Ontology edit history (so we can trace any bad suggestion that got in and roll it back).
  * These logs can be reviewed periodically to improve the system. For example, if we see GPT often suggesting a certain addition, maybe the teachers should preemptively add more scenarios in that area.
* **Performance Monitoring:** Ensure the response times are reasonable. If GPT calls become a bottleneck, consider introducing an asynchronous queue for GPT processing so the web request can return quickly with a “bot is thinking...” and update when ready (though this complicates real-time chat feel). For now, we assume usage volume is moderate and GPT is called synchronously with an acceptable delay (\~1-2 seconds for a response).
* **Security:** Since we use PHP/JS, we must handle security basics:

  * Protect against XSS in the chat (escape any user input before displaying it back anywhere, even in logs or admin panels).
  * Protect against SQL injection by using prepared statements for any SQL (or proper escaping, especially if constructing queries for search).
  * The GPT prompt should be carefully constructed to not inadvertently reveal sensitive info (though in this context that’s minimal, but e.g., don’t feed it raw database rows without need).
  * API keys and secrets must be stored server-side securely (not visible to client).
* **Extensibility:** Design the code with separation of concerns:

  * For instance, have a separate PHP class or module for “OntologyManager” (handles fetching, updating ontology), “ChatbotResponder” (handles the logic of picking strategies and calling GPT), etc. This not only makes the code easier to navigate but also easier to modify one part (like swapping out GPT for another model, or changing how content is retrieved) without breaking everything.
  * Comment the code and document the functions so that if a coding agent or another developer reads it, they can follow the intended logic. This is especially important because multi-turn logic can become tricky to follow.

By anticipating possible points of failure and designing countermeasures, the system stays **stable**. The combination of incremental development, thorough testing, and built-in safety checks will result in a reliable chatbot that teachers and students can trust. Each module (ontology data, user interface, GPT interaction, etc.) can be maintained and upgraded independently as needed, ensuring longevity of the system.

## 11. Example Use Case Walk-Through

To illustrate how the pieces come together, let’s walk through a concrete example scenario, combining everything:

* **Initial Setup:** A teacher has entered an ontology entry for *“Student is frustrated with a problem and wants to give up”* (as we’ve used in examples). Two strategies were added:

  1. *Offer an easier problem to build confidence* (content card: Easy Practice Problem ID #101),
  2. *Provide a concept refresher* (content card: link to a short video explaining the concept).
* **Student Question:** During a homework session, a student types: *“선생님, 이 문제 너무 어려워요. 저 그냥 포기할래요.”* (“Teacher, this problem is too hard. I just want to give up.”).
* **Chatbot Response Generation:**

  1. The system’s keyword matcher finds “어려워요” and “포기” which matches the above situation. It fetches that ontology entry.
  2. Strategy 1 (easier problem) is chosen first (perhaps it’s the first in list). The system knows content ID 101 corresponds to “Basic Algebra Problem #1”.
  3. The PHP backend calls GPT (with a prompt containing the situation and strategy info) to compose a friendly reply. GPT returns: *“많이 어려웠구나. 괜찮아요! 지금 문제는 잠시 접어두고, 내가 고른 **쉬운 연습문제** 하나 풀어볼까? 작은 성공을 통해 자신감을 찾아보자.”* (“That was really hard, huh? It’s okay! Let’s set aside that problem for now and try this **easy practice problem** I’ve picked. Through a small success, let’s find your confidence.”).
  4. The bot sends this text along with the content card for the easy problem to the front-end.
  5. The student sees the message and a card saying “쉬운 연습문제” (easy practice problem). They click it, and the problem statement pops up (we had stored the text for ID 101: e.g., “Solve: 2x + 3 = 7”).
* **Student Follows Up:** The student solves the easier problem and says: *“풀었어요! 근데 원래 문제랑 뭐가 달라요?”* (“I solved it! But how is this different from the original problem?”).
* **Chatbot Continued Support:**

  1. The chatbot knows the last situation was “frustrated/giving up” and that one strategy was used. The student now is asking how the easy problem relates back. This indicates the student might need a bridge to the original problem.
  2. The system decides to now use Strategy 2 (concept refresher) because the student has gained some confidence but still doesn’t see the link.
  3. It calls GPT to generate a response that ties the easy problem to the concept: GPT produces something like: *“잘했어요! 방금 푼 문제를 통해, 원래 문제의 개념인 '방정식 풀기'를 연습해본 거야. 이제 영상을 하나 볼 텐데, 아까 문제를 푸는 데 필요한 개념을 정리해 줄 거란다. 그리고 나서 원래 문제를 다시 보면 조금 더 쉬워질 거야!”* (“Well done! Through the problem you just solved, you practiced the concept of solving equations needed for the original problem. Now, let's watch a short video that will summarize the concept you need. After that, the original problem will feel easier!”).
  4. The bot sends this with the video content card (title “방정식 풀이 개념 영상”). The student clicks and watches the video.
* **Outcome:** After the video, the student says: *“아! 이제 원래 문제 어떻게 풀지 알 것 같아요. 감사합니다.”* (“Ah! I think I know how to solve the original problem now. Thank you.”). The chatbot gives a final encouragement: *“좋았어! 이제 해볼 수 있겠죠? 문제 풀다가 또 모르겠으면 언제든지 물어보렴. 화이팅!”* (“Great! You can do it now, right? If you get stuck again, ask anytime. You got this!”).
* **Ontology Refinement:** Meanwhile, in the background, the system logs this conversation. Suppose the student’s phrasing had a nuance the system wasn’t fully prepared for (maybe the student also said something like “수학은 체질이 아니야” – “Math isn’t my thing”). GPT analysis might later suggest: *“Student expressed a general dislike for math, which might be a different situation than just this problem difficulty. Consider an entry for math aversion.”* The teacher sees this suggestion in the admin panel, and decides to add a new situation “Student lacks interest in math overall” with strategies like “connect math to personal interests” in the ontology for future use.

This example shows how the **system behaves in a real use-case** and how the design elements function:

* The ontology mapping ensured the response was on-point for the student’s emotional state.
* GPT provided fluent, contextually appropriate language in Korean, and helped adapt the help as the situation evolved.
* Content cards gave the student concrete actions (solving a problem, watching a video) rather than just words.
* The multi-turn flow allowed the bot to react to the student’s progress (first giving an easier task, then bridging back to the original task).
* Finally, the self-improving aspect kicks in to broaden the ontology for cases of *math aversion*, making the system smarter for the next student who might say “I just hate math.”

## 12. Conclusion

Designing this GPT-assisted educational chatbot system involves balancing **structured educational logic** with **AI-driven flexibility**. By using a teacher-curated ontology (stored as JSON in MySQL) as the backbone, the system guarantees pedagogically appropriate responses – each student issue is met with a strategy that teachers have approved. The integration of GPT-4 API brings in the ability to understand nuanced student language and to generate supportive, natural-sounding replies, as well as to continuously refine the knowledge base through multi-turn feedback. We emphasized strategies for minimizing token usage (sending only relevant context, using templates and simple logic first) to keep the system efficient and responsive. The entire architecture is built with **incremental development in mind** – each component (data schema, input interface, chat logic, GPT calls, refinement loop) can be developed and tested step by step, reducing the risk of getting lost in complexity.

Importantly, the design adheres to the given technology stack: a PHP backend (for ease of integration into typical web servers and to interface with MySQL), a MySQL database for reliability and familiarity, JSON data formatting for flexibility, and HTML/JS/CSS on the front-end for an interactive user experience. There is no reliance on exotic frameworks or languages, which means a wider range of developers or a coding agent can implement it.

By following this blueprint, one can implement a **robust, interactive classroom chatbot** that not only helps students in the moment with personalized guidance and content, but also learns and improves its knowledge base over time with minimal human intervention. The end result will be a “교실 챗봇 (classroom chatbot)” that operates reliably within the safe bounds of teacher guidance, yet feels adaptive and intelligent thanks to GPT – all delivered through a well-structured, maintainable system design ready for coding.
