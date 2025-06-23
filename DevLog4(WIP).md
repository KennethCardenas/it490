# Dev Log: 04
## Date / Module: June 19, 2025 / Module 4
## Name: Kenneth Cardenas (kac63)
 
### GitHub Issue Links Assigned
- [ ] Authentication System Implementation
  - Acceptance Criteria:
    - Registration page with email, username, password fields
    - Login page accepting email or username
    - Protected landing page
    - Profile editing functionality
    - Session-based navigation
    - MQ integration for all DB operations
  - Started Date: June 19, 2025
  - Target Completion Date: June 25, 2025 
  - Finished Date: N/A
  - Summary of individual contribution for this entry: 
    - Designed registration form HTML/CSS
    - Implemented client-side validation using JavaScript
    - Set up bcrypt password hashing
    - Created MQ producer/consumer for user registration

- [ ] Navigation Component
  - Acceptance Criteria:
    - Dynamic links based on auth state
    - Responsive design
    - Session awareness
  - Started Date: June 19, 2025
  - Target Completion Date: June 23, 2025
  - Finished Date: N/A
  - Summary of individual contribution for this entry:
    - Basic navbar structure
    - Auth state detection logic
    - CSS styling

### Noteworthy Learnings and resource links
- RabbitMQ tutorial: https://www.rabbitmq.com/getstarted.html
- bcrypt documentation: https://www.npmjs.com/package/bcrypt
- Session management: https://expressjs.com/en/resources/middleware/session.html
- Learned about message queue patterns for decoupled architecture
- Discovered importance of salt rounds in password hashing

### Problems/Difficulties Encountered
- MQ connection issues between frontend and backend services
- Session cookie not persisting on page reload
- Password confirmation field validation logic
- Timezone differences causing coordination challenges
- Conflict between client-side and server-side validation messages

### Positive Shoutout to Team Member(s)
- Antonio for helping out on the MQ flow and DB testing
- Jerry for helping create the necessary pages
- Filipe for styling all the pages and cleaning them up
- Jhonathan for helping with the MQ files

### What can be improved individually?
- More frequent Git commits with better messages
- Earlier testing of edge cases
- Better documentation of environment variables
- More proactive communication about blockers

### What can be improved as a team?
- Daily standups to sync progress
- Shared testing checklist
- Better division of frontend/backend tasks
- Earlier integration testing schedule
- Consolidated documentation approach
