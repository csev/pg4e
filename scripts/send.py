import myutils

from email.message import EmailMessage

message = """\
Subject: Hi there

This YADA message is sent from Python."""

myutils.sendMail(message, "csev@umich.edu")

