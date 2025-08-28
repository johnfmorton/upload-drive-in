# Tool Usage Patterns and Common Mistakes

## String Replacement Tool (`strReplace`)

### Critical Requirements
- **ALWAYS** provide both `oldStr` and `newStr` parameters
- **NEVER** call `strReplace` with only `oldStr` - this will cause an "Invalid operation - missing newStr" error
- When removing text, use `newStr: ""` (empty string) to delete the content
- When removing lines, ensure proper line break handling in the replacement

### Common Patterns
```
// ❌ WRONG - Missing newStr parameter
strReplace(path: "file.php", oldStr: "debug code here")

// ✅ CORRECT - Removing content
strReplace(path: "file.php", oldStr: "debug code here", newStr: "")

// ✅ CORRECT - Replacing content  
strReplace(path: "file.php", oldStr: "old code", newStr: "new code")
```

### Error Prevention
- If you want to remove text completely, always use `newStr: ""`
- If you get stuck in a loop with strReplace errors, stop and use `fsWrite` to rewrite the entire file instead
- For large removals or complex changes, consider using `fsWrite` rather than multiple `strReplace` calls

### Alternative Approaches
- For complete file rewrites: Use `fsWrite`
- For adding content: Use `fsAppend`  
- For deleting entire files: Use `deleteFile`