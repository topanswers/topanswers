#!/bin/bash

ip=$(wget -qO - icanhazip.com)

#aws ec2 modify-security-group-rules \
#    --group-id sg-0d230def00269dadb \
#    --security-group-rules "SecurityGroupRuleId=sgr-0caf535bb0740e5d3,SecurityGroupRule={Description=Codespaces,IpProtocol=6,FromPort=5432,ToPort=5432,CidrIpv4=$ip/32}"
