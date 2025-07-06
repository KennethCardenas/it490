# Dev Log: 06  
## Date / Module: July 6, 2025 / Module 6  
## Name: Kenneth Cardenas  

### GitHub Issue Links Assigned  
- [x] System Service Implementation  
  - Acceptance Criteria:  
    - Functional DB and API consumers  
    - Proper .service file configurations  
    - Network-aware startup  
    - Auto-recovery functionality  
  - Started Date: June 23, 2025  
  - Target Completion Date: June 26, 2025  
  - Finished Date: June 26, 2025  
  - Summary of individual contribution:  
    - Coordinated service file testing  
    - Documented recovery procedures  
    - Assisted with recording preparation  

- [x] Cross-VM Validation  
  - Acceptance Criteria:  
    - Consistent behavior across environments  
    - Verified message durability  
    - Confirmed proper service sequencing  
  - Started Date: July 2, 2025  
  - Target Completion Date: July 2, 2025  
  - Finished Date: July 2, 2025  
  - Summary of individual contribution:  
    - Tested service interactions  
    - Validated failover scenarios  
    - Reviewed test consumer outputs  

### Noteworthy Learnings and resource links  
- Service file best practices: https://www.digitalocean.com/community/tutorials/how-to-use-systemctl  
- RabbitMQ durability settings: https://www.rabbitmq.com/queues.html  
- Discovered importance of `Wants=network-online.target`  
- Learned effective use of `journalctl` for debugging  
- Confirmed value of `RestartSec` for staggered recovery  

### Problems/Difficulties Encountered  
- Intermittent queue connection drops during testing  
- Permission conflicts with PHP consumer scripts  
- VM clock sync issues affecting timestamps  
- Service dependency resolution challenges  
- Audio sync issues during recording  

### Positive Shoutout to Team Member(s)  
- Filip: Developed the robust api_consumer.php script and perfected its .service file configuration  
- Jonathan: Set up comprehensive test consumers on node VMs that revealed critical timing issues  
- Chris: Identified and resolved queue durability mismatches that prevented message persistence  
- Antonio: Verified all service reboot scenarios and documented startup status checks  
- Jerry: Orchestrated multi-VM testing that ensured cross-environment stability  

### What can be improved individually?  
- Earlier adoption of structured logging  
- More frequent commit atomicity  
- Better pre-testing of recording setup  
- More proactive documentation updates  

### What can be improved as a team?  
- Standardized VM configurations earlier  
- Shared debugging notes more systematically  
- Allocated more buffer time for recording  
- Implemented pair testing for critical components  
