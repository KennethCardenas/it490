# Dev Log: 06  
## Date / Module: July 7, 2025 / Module 6  
## Name: Kenneth Cardenas  

### GitHub Issue Links Assigned  
- Profile Integration  
- Sitter Lookup + Access Granting  
- Merge Conflict Resolution  
- MQ Handler Expansion  

### Acceptance Criteria  
- Dynamic sitter listing with `list_sitters` queue  
- Owner role gatekeeping on access actions  
- Merged and tested all profile and access view routes  
- Verified end-to-end access grants via MQ  

### Started Date: July 7, 2025  
### Target Completion Date: July 7, 2025  
### Finished Date: July 7, 2025  

### Summary of individual contribution  
- Cleaned and finalized `grant_access.php`, `sitters.php`, and profile files  
- Validated end-to-end sitter access workflows using mock users  
- Merged active code branches and eliminated merge conflict residue  
- Updated `worker.php` with `grant_dog_access`, `list_sitters`, and sitter-related handlers  
- Added strict `isOwner()` checks and verified access control flows  

### Noteworthy Learnings and resource links  
- Resolved real-world merge conflicts: learned Git rebase vs. merge priorities  
- Explored `htmlspecialchars()` for safe UI output  
- Debugged multi-parameter `bind_param()` behavior with `null` values  
- Relearned proper use of PHP's optional chaining (`??`) for fallback defaults  
- Git merge strategies: https://www.atlassian.com/git/tutorials/using-branches/merge-strategy  
- PHP `bind_param` type definitions: https://www.php.net/manual/en/mysqli-stmt.bind-param.php  

### Problems/Difficulties Encountered  
- Merge conflicts between `define-mvp` and `implement-profiles` branches  
- Misalignment between expected sitter schema and database responses  
- Conflicting navbar links from different role-check implementations  
- Sitter ID mismatch causing access logic to silently fail  
- Race conditions with session vs. payload validation  
- Auto-formatting cluttered git diffs during final PR  

### Positive Shoutout to Team Member(s)  
- Filip: Caught and fixed an escaping issue in the sitter listing UI  
- Jonathan: Validated that merged profile and sitter views didn’t break legacy routes  
- Chris: Helped untangle merge conflicts and reviewed the final `worker.php` switch-case logic  
- Antonio: Rewrote malformed MQ responses for sitter profiles and tested all response paths  
- Jerry: Triggered `grant_access` end-to-end from UI and confirmed MQ handler success  

### What can be improved individually?  
- Shorter, focused commits during multi-file merges  
- Earlier review of existing auth role check logic  
- More disciplined testing of edge cases (e.g., no sitters, expired access)  

### What can be improved as a team?  
- Align route file naming across branches before merging  
- Reuse more UI components to prevent duplicate logic (e.g., sitter display card)  
- Standardize `auth.php` logic for role checks in one place  
- Better coordination of live test data vs mock data during demos  
