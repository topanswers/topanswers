#!/bin/bash
aws ec2 modify-security-group-rules --group-id $AWS_SG --security-group-rules SecurityGroupRuleId=$AWS_SGR,SecurityGroupRule="{Description='HOME PG',IpProtocol=-1,CidrIpv4=$(curl https://ipinfo.io/ip)/32}"
