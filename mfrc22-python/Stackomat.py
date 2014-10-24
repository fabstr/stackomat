#!/usr/bin/env python
# -*- coding: utf8 -*-

import RPi.GPIO as GPIO
import MFRC522
import signal
import socket
import struct

continue_reading = True

# Capture SIGINT for cleanup when the script is aborted
def end_read(signal,frame):
    global continue_reading
    print "Ctrl+C captured, ending read."
    continue_reading = False
    GPIO.cleanup()

# Hook the SIGINT
signal.signal(signal.SIGINT, end_read)

# Create an object of the class MFRC522
MIFAREReader = MFRC522.MFRC522()

# open socket
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

# This loop keeps checking for chips. If one is near it will get the UID and authenticate
while continue_reading:
    # Scan for cards    
    (status,TagType) = MIFAREReader.MFRC522_Request(MIFAREReader.PICC_REQIDL)

    # Get the UID of the card
    (status,uid) = MIFAREReader.MFRC522_Anticoll()

    # If we have the UID, continue
    if status == MIFAREReader.MI_OK:

	idint = 0
	idint += uid[3]*pow(2,24)
	idint += uid[2]*pow(2,16)
	idint += uid[1]*pow(2,8)
	idint += uid[0]
	print idint

        # id = [uid[0], uid[1], uid[2], uid[3]];
	idstr = str(idint) + "\n"
        sock.sendto(idstr, ("127.0.0.1", 12345))
