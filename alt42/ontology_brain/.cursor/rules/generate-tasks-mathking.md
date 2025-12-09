Rule: Generating a Task List from a PRD
Goal

To guide an AI assistant in creating a detailed, step-by-step task list in Markdown format based on an existing Product Requirements Document (PRD).
The task list should guide a developer through implementation while ensuring clarity, safety, and non-duplication.

Output

Format: Markdown (.md)

Location: /tasks/

Filename: tasks-[prd-file-name].md (e.g., tasks-0001-prd-user-profile-editing.md)

Process

Receive PRD Reference:
The user points the AI to a specific PRD file.

Analyze PRD:
The AI reads and analyzes the functional requirements, user stories, and other sections of the specified PRD.
If any requirement is unclear, prompt the user for clarification before generating tasks.

Assess Current State:
Review the existing codebase to understand existing infrastructure, architectural patterns, and conventions.
Identify existing components or features that could be relevant to the PRD requirements.
Then, identify existing related files, components, and utilities that can be leveraged or need modification.
Check if planned changes could affect database integrity or duplicate existing logic, and raise a clarification question if needed.

Phase 1: Generate Parent Tasks:
Based on the PRD analysis and current state assessment, create the file and generate the main, high-level tasks required to implement the feature.
Use your judgement on how many high-level tasks to use — typically around five.
Present these tasks to the user in the specified format (without sub-tasks yet).
Inform the user:

"I have generated the high-level tasks based on the PRD. Ready to generate the sub-tasks? Respond with 'Go' to proceed."

Wait for Confirmation:
Pause and wait for the user to respond with "Go".

Phase 2: Generate Sub-Tasks:
Once the user confirms, break down each parent task into smaller, actionable sub-tasks necessary to complete the parent task.
Ensure sub-tasks logically follow from the parent task, cover the implementation details implied by the PRD, and consider existing codebase patterns where relevant without being constrained by them.

Identify Relevant Files:
Based on the tasks and PRD, identify potential files that will need to be created or modified.
List these under the Relevant Files section, including corresponding test files if applicable.

Run Validation Checklist:
Before finalizing the task list, perform an internal validation cycle to ensure the following:

Database consistency and schema safety

Avoidance of duplicate logic or overlapping features

Adequate test coverage for new or modified files

Inclusion of error handling and edge-case tasks

Security and performance considerations where applicable

If any uncertainty arises in these checks, pause and prompt the user before generating the final output.

Generate Final Output:
Combine the parent tasks, sub-tasks, relevant files, and notes into the final Markdown structure.
Include rollback or deployment readiness notes if the feature involves database or API changes.

Save Task List:
Save the generated document in the /tasks/ directory with the filename tasks-[prd-file-name].md,
where [prd-file-name] matches the base name of the input PRD file (e.g., if the input was 0001-prd-user-profile-editing.md,
the output is tasks-0001-prd-user-profile-editing.md).

Output Format

The generated task list must follow this structure:

## Relevant Files

- `path/to/potential/file1.ts` - Brief description of why this file is relevant (e.g., Contains the main component for this feature).
- `path/to/file1.test.ts` - Unit tests for `file1.ts`.
- `path/to/another/file.tsx` - Brief description (e.g., API route handler for data submission).
- `path/to/another/file.test.tsx` - Unit tests for `another/file.tsx`.
- `lib/utils/helpers.ts` - Brief description (e.g., Utility functions needed for calculations).
- `lib/utils/helpers.test.ts` - Unit tests for `helpers.ts`.

### Notes

- Unit tests should typically be placed alongside the code files they are testing (e.g., `MyComponent.tsx` and `MyComponent.test.tsx` in the same directory).
- Use `npx jest [optional/path/to/test/file]` to run tests. Running without a path executes all tests found by the Jest configuration.
- Include rollback or deployment readiness notes if the task list involves database or API changes.

## Tasks

- [ ] 1.0 Parent Task Title
  - [ ] 1.1 [Sub-task description 1.1]
  - [ ] 1.2 [Sub-task description 1.2]
- [ ] 2.0 Parent Task Title
  - [ ] 2.1 [Sub-task description 2.1]
- [ ] 3.0 Parent Task Title (may not require sub-tasks if purely structural or configuration)

Interaction Model

The process explicitly requires a pause after generating parent tasks to get user confirmation ("Go") before proceeding to generate detailed sub-tasks.
This ensures the high-level plan aligns with user expectations and prevents unsafe or unnecessary expansion.

Target Audience

Assume the primary reader of the task list is a junior developer who will implement the feature with awareness of the existing codebase context.
The generated document must therefore emphasize clarity, safety, and verification checkpoints over automation.