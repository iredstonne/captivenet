# CaptiveNet
## Last push: 02/06/25
![Project Status](https://img.shields.io/badge/status-in--development-orange)

# Requirements
- Internet connection
- Root or sudo access
- Debian Bookworm 12 
- Ethernet port connected to ISP (eth0)
- Wi-Fi card with AP mode support

# Setup
```bash 
git clone https://github.com/iredstonne/captivenet /opt/captivenet
cd /opt/captivenet
bash ./install_debian.sh
# Use your phone and connect to the hotspot wifi.
# You will be redirected automatically to captive portal web page if using Android, Apple or Windows. 
#If redirection doesn't happen, navigate connected to the hotspot to http://captivenet.local
```
