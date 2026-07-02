```markdown
# miki-kesel-portfolio Development Patterns

> Auto-generated skill from repository analysis

## Overview
This skill provides guidance on the development patterns and conventions used in the `miki-kesel-portfolio` TypeScript codebase. It covers file naming, import/export styles, commit message patterns, and how to write and organize tests. While no explicit frameworks or automated workflows were detected, this guide will help maintain consistency and productivity in this repository.

## Coding Conventions

### File Naming
- **Pattern:** PascalCase for files and components.
- **Example:**  
  ```
  MyComponent.ts
  UserProfile.test.ts
  ```

### Import Style
- **Pattern:** Relative imports are used for referencing other files.
- **Example:**
  ```typescript
  import { UserProfile } from './UserProfile';
  ```

### Export Style
- **Pattern:** Named exports are preferred over default exports.
- **Example:**
  ```typescript
  // In UserProfile.ts
  export function UserProfile() { ... }

  // In another file
  import { UserProfile } from './UserProfile';
  ```

### Commit Messages
- **Pattern:** Freeform, no strict prefixes.  
- **Average Length:** ~51 characters.
- **Example:**  
  ```
  Add new contact section to portfolio page
  ```

## Workflows

### Adding a New Component
**Trigger:** When you need to add a new UI or logic component.
**Command:** `/add-component`

1. Create a new file using PascalCase (e.g., `NewFeature.ts`).
2. Use named exports for your component or function.
3. Import dependencies using relative paths.
4. If applicable, create a corresponding test file (e.g., `NewFeature.test.ts`).

### Writing Tests
**Trigger:** When you need to verify the functionality of code.
**Command:** `/write-test`

1. Create a test file with the `.test.ts` suffix (e.g., `UserProfile.test.ts`).
2. Write tests using your preferred testing framework (not specified in repo).
3. Place test files alongside the source files or in a dedicated test directory.

## Testing Patterns

- **File Pattern:** Test files are named with the `.test.*` suffix, such as `Component.test.ts`.
- **Framework:** Not specified; choose a TypeScript-compatible test runner (e.g., Jest, Mocha).
- **Example:**
  ```typescript
  // UserProfile.test.ts
  import { UserProfile } from './UserProfile';

  describe('UserProfile', () => {
    it('should render correctly', () => {
      // Test implementation here
    });
  });
  ```

## Commands
| Command         | Purpose                                  |
|-----------------|------------------------------------------|
| /add-component  | Scaffold and add a new component         |
| /write-test     | Create and write tests for a component   |
```
