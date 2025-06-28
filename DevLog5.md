# Dev Log: 05
## Date / Module: June 28, 2025 / Module 5
## Name: Kenneth Cardenas (kac63)

### GitHub Issue Links Assigned
- [ ] System Service Implementation
  - Acceptance Criteria:
    - Create system service for DB MQ Consumer
    - Create system service for API MQ Consumer
    - Services start on boot
    - Services restart on failure
    - Network availability check
  - Started Date: June 19, 2025
  - Target Completion Date: June 30, 2025
  - Finished Date: N/A
  - Summary of individual contribution for this entry:
    - Researched systemd service configuration
    - Created initial draft of DB MQ Consumer service file
    - Tested service restart behavior
    - Collaborated on network availability check implementation

### Noteworthy Learnings and resource links
- Learned about systemd unit file syntax and directives
- Discovered importance of proper service dependencies
- Understanding of process supervision patterns

### Problems/Difficulties Encountered
- Determining correct network availability check method
- Service restart timing issues
- Permission challenges with service files
- Debugging service failures
- Coordinating testing across team members' environments

### Positive Shoutout to Team Member(s)
- Antonio for troubleshooting service dependencies
- Jerry for testing on multiple VM configurations
- Filipe for documenting the service configuration options
- Jhonathan for coordinating the group testing schedule
- chris for helping solve bug issues

### What can be improved individually?
- More thorough testing of edge cases
- Better documentation of service configurations
- Earlier communication about environment differences
- More detailed commit messages for service files

### What can be improved as a team?
- Standardized testing approach across environments
- Shared checklist for service requirements
- Better division of testing responsibilities
- Consolidated documentation of service configurations
