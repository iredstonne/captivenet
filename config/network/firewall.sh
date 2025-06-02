#!/bin/bash
HOST_ADDRESS="192.168.4.1"
AUTHENTICATED_SETNAME="authenticated"
AUTHENTICATED_MARK=0x1

# Create ipset for authenticated clients
ipset create "$AUTHENTICATED_SETNAME" hash:ip -exist
ipset flush authenticated -exist

# Remove existing iptables rules in memory
iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X
iptables -t mangle -F
iptables -t mangle -X

# Set default policies
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# Mark packets as authenticated if in ipset
sudo iptables -t mangle -A PREROUTING -m set --match-set "$AUTHENTICATED_SETNAME" src -j MARK --set-mark "$AUTHENTICATED_MARK"

# Allow established connections on all interfaces
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

# Allow everything on loopback (localhost)
iptables -A INPUT -i lo -j ACCEPT

# Allow everything on eth0
iptables -A INPUT -i eth0 -j ACCEPT

# Allow DHCP and DNS on wlan0
iptables -A INPUT -i wlan0 -p udp --dport 53 -j ACCEPT
iptables -A INPUT -i wlan0 -p tcp --dport 53 -j ACCEPT
iptables -A INPUT -i wlan0 -p udp --dport 67 -j ACCEPT

# Allow HTTP traffic on wlan0
iptables -A INPUT -i wlan0 -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -i wlan0 -p tcp -m mark ! --mark $AUTHENTICATED_MARK --dport 443 -j REJECT --reject-with tcp-reset
iptables -A INPUT -i wlan0 -p tcp --dport 443 -j ACCEPT

# DNS traffic redirection
iptables -t nat -A PREROUTING -i wlan0 -p udp --dport 53 -j DNAT --to-destination $HOST_ADDRESS:53
iptables -t nat -A PREROUTING -i wlan0 -p tcp --dport 53 -j DNAT --to-destination $HOST_ADDRESS:53

# HTTP traffic redirection
iptables -t nat -A PREROUTING -i wlan0 -p tcp -m mark ! --mark $AUTHENTICATED_MARK --dport 80 -j DNAT --to-destination $HOST_ADDRESS:80

# Client Isolation
iptables -A FORWARD -i wlan0 -o wlan0 -d $HOST_ADDRESS -j ACCEPT
iptables -A FORWARD -i wlan0 -o wlan0 -s $HOST_ADDRESS -j ACCEPT
iptables -A FORWARD -i wlan0 -o wlan0 -j DROP

# Internet Access
iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
iptables -A FORWARD -i wlan0 -o eth0 -m mark --mark $AUTHENTICATED_MARK -j ACCEPT

sudo iptables-save | sudo tee /etc/iptables/rules.v4
sudo ipset save | sudo tee /etc/iptables/ipsets
