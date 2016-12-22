# extensions.conf
[globals]

RECDIR=/var/spool/asterisk/monitor

SAVEMP3DIR=/www/light/monitor

[dongle-incoming-sms]

exten => sms,1,Noop(Incoming SMS from ${CALLERID(num)} ${BASE64_DECODE(${SMS_BASE64})} - ${SMS})

exten => sms,n, Set(CDR(call_num)=sms_ussd)

exten => sms,n,AGI(sms.php, ${CALLERID(num)}, ${SMS})

exten => sms,n,Hangup()


[dongle-incoming-ussd]

exten => ussd,1,Noop(Incoming USSD: ${BASE64_DECODE(${USSD_BASE64})})

exten => ussd,n, Set(CDR(call_num)=sms_ussd)

exten => ussd,n,AGI(ussd.php, ussd, ${USSD})

exten => ussd,n,Hangup()


[all-in]


include => dongle-incoming-sms

include => dongle-incoming-ussd

exten => _X., 1, Set(MON_FILE=${UNIQUEID}-${STRFTIME(${EPOCH},,%Y-%m-%d-%H_%M)}-${CALLERID(num)}-${CDR(dst)})

exten => _X., 2, Set(CDR(userfield)=${MON_FILE});

exten => _X., 3, Set(CDR(call_num)=${CALLERID(num)})

exten => _X., 4, Set(CDR(dist)=in)

exten => _X., 5, MixMonitor(${MON_FILE}.wav)

exten => _X., 6, AGI(in.php)                                   ; направлены на внутренний номер 

exten => _X., 7, Hangup()

exten => _X., 8, StopMixMonitor()

exten => _+X., 1, Set(MON_FILE=${UNIQUEID}-${STRFTIME(${EPOCH},,%Y-%m-%d-%H_%M)}-${CALLERID(num)}-${CDR(dst)})

exten => _+X., 2, Set(CDR(userfield)=${MON_FILE});

exten => _+X., 3, Set(CDR(call_num)=${CALLERID(num)})

exten => _+X., 4, Set(CDR(dist)=in)

exten => _+X., 5, MixMonitor(${MON_FILE}.wav)

exten => _+X., 6, AGI(in.php)                                   ; направлены на внутренний номер

exten => _+X., 7, Hangup()

exten => _+X., 8, StopMixMonitor()

exten => h,1,System(/usr/bin/lame -b 16 -silent ${RECDIR}/${MON_FILE}.wav ${SAVEMP3DIR}/${MON_FILE}.mp3 > /var/log/asterisk/wav_2_mp3.log)

exten => h,n,System(/bin/rm -r ${RECDIR}/${MON_FILE}.wav)


#------------------------------------------------------------

#cdr_sqlite3_custom.conf


table => cdr

columns => calldate, clid, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield, call_num, dist

values => '${CDR(start)}','${CDR(clid)}','${CDR(dcontext)}','${CDR(channel)}','${CDR(dstchannel)}','${CDR(lastapp)}','${CDR(lastdata)}','${CDR(duration)}','${CDR(billsec)}','${CDR(disposition)}','${CDR(amaflags)}','${CDR(accountcode)}','${CDR(uniqueid)}','${CDR(userfield)}','${CDR(call_num)}','${CDR(dist)}'
