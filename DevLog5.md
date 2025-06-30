# Dev Log: 05  
## Date / Module: June 28, 2025 / Module 5  
## Name: Kenneth Cardenas (kac63)

### GitHub Issue Links Assigned
- [x] Configure and Test DB MQ Consumer Service  
  - **Acceptance Criteria:**  
    - Create system service for DB MQ Consumer  
    - Ensure service starts on boot  
    - Confirm service restarts on failure  
    - Waits for network availability before starting  
  - **Started Date:** June 19, 2025  
  - **Target Completion Date:** June 30, 2025  
  - **Finished Date:** June 28, 2025  
  - **Summary of individual contribution for this entry:**  
    - Researched systemd service configuration  
    - Created and deployed the `db_consumer.service` file  
    - Resolved `status=217/USER` and other common systemd issues  
    - Validated service startup, network wait conditions, and auto-restart behavior

### Noteworthy Learnings and resource links
- Learned systemd unit file structure and directives  
- Understood how to link service dependencies to `network-online.target`  
- Gained experience troubleshooting process supervision and service lifecycles  
- Verified MQ and DB integration through consumer logs and service recovery  

### Problems/Difficulties Encountered
- Matching system users and file paths across VMs  
- `ExecStart` failures due to incorrect PHP paths or permissions  
- RabbitMQ queue declaration mismatches causing service exit  
- Conflicts between script behavior and systemd `Type` defaults  
- Debugging across VMs with varying directory structures  

### Positive Shoutout to Team Member(s)
- **Filip** – for developing the `api_consumer.php` script and configuring its `.service` file  
- **Jonathan** – for setting up and validating the test consumer on a node VM  
- **Chris** – for identifying and helping resolve queue durability mismatches  
- **Antonio** – for verifying successful service reboots and startup status  
- **Jerry** – for supporting multi-VM testing to ensure cross-environment stability  

### What can be improved individually?
- Improve time estimates when testing services across multiple environments  
- Add inline comments in `.service` files for better documentation  
- Use clearer commit messages for configuration changes  
- Run full end-to-end tests before pushing MQ changes  

### What can be improved as a team?
- Assign one person to validate each consumer per VM  
- Keep consistent file and folder structure across all VMs  
- Create a central test plan checklist with pass/fail tracking  
- Document each working `.service` configuration with screenshots and paths for others to follow
