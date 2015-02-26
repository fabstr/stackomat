#!/bin/sh

# start the python script reading from the rfid reader
# restart the script when it exits
while true
do
	echo 'Starting rfid reader'
	/usr/bin/python /home/stackomat/mfrc22-python/Stackomat.py 2>&1 > /dev/null
done
