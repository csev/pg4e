import os
import myutils

trigger = 57
size = -1
dfcommand = "df -Ph | grep '/dev/nvme0n1p1' | awk '{ print $5}' | sed 's/%//'"
zap = os.popen(dfcommand).readlines();

try:
    size = int(zap[0].strip())
except:
    size = -1

if size > 0 and size < trigger:
    print(f"Disk space ({size}%) below trigger ({trigger}%)")
    quit()

if size > 0 and size >= trigger:
    subject = f"PG4E: Disk Space {size}% Exceeds Threshold of {trigger}%"
else:
    subject = f"PG4E: Disk Space Got Incorect Data From df Command"

message = "command: " + dfcommand + "\n\n";
message = message + "popen returned "+str(len(zap))+" line(s)\n\n"
for line in zap:
    message = message + line

print(message)
print(myutils.sendNotification(subject,message))

