# -*- encoding: UTF-8 -*-

import requests
import codecs
import base64
import datetime
import json
import sys
import motion
import almath
import ssl
import subprocess
import threading
import time
import os
import uuid
import argparse
import requests.packages.urllib3
import urllib2
import socket
import re

from multiprocessing import Process, Manager, Pipe
from gtts import gTTS 
from logger import myLog
from user_config import *
from sys_config import *
from greetings import *
from language_keyword import *
from error_messages import *
from collections import namedtuple
from naoqi import ALProxy
from naoqi import ALModule
from naoqi import ALBroker
from random import randint
from google.cloud import speech
from google.cloud.speech import enums
from google.cloud.speech import types
from six.moves import queue
from ctypes import *
from contextlib import contextmanager
from signal import signal, SIGPIPE, SIG_DFL

__version__ = "1.1.0"
__changelog__ = """
- Added more condition for detection to be considered indecisive
Previously it was only if the transcription returns nothing. 
Now it also depend on confidence value.
"""

# Handle Broken Pipe
signal(SIGPIPE,SIG_DFL)

# Global variable to store module instance
HumanGreeter = None
CSVWriter = None
stt_client = None
memory = None
lastResponseFromAPI = ""
myBroker = None

# Flag
customerDetected = False
conversationMode = False
conversationStarted = False
conversationEndTrigger = False
QMSTrigger = False
sttEndTrigger = False
exceptionDuringAudioStreaming = False
isSTTClientOpened = False
isConnectionError = False
isProgramError = False
speechLangCode_Conversation = "en"
speechLangCode_GoogleSTT = "en"
speechLangDetected = False
speechLangIndecisive = False
exceptionDuringWebSocketInit = True
customerDetectedEvent = threading.Event()
sttEndTriggerEvent = threading.Event()
sttInitializedEvent = threading.Event()
webSocketClosedEvent = threading.Event()
initialConnectionCheck = threading.Event()
vmachine = False
# websocket_open_attempt = 1

create_websocket_thread=None

#variable
loggerLevel = defaultLoggerLevel
stdoutLevel = defaultStdoutLevel
SAVE_RECORDING = True
conversationId = ""
requestId = ""
locale = "en-us"

#set requests session
session = requests.session()

#disable ssl warning
requests.packages.urllib3.disable_warnings()

#disable _DummyThread error message
threading._DummyThread._Thread__stop = lambda x: 42

#logging
STTInitiateTime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
STTStartTime = time.time()
STTEndTime = time.time()
detectLanguageEndTime = time.time()
conversationEndTime = time.time()
TTSTimeTaken = time.time()
TTSEndTime = time.time()
roundtripTime = time.time()
conversationTimeTaken = time.time()

#initialize paths
soundPath = programPath.rstrip('/')+"/sound/"
logPath = programPath.rstrip('/')+"/log/"

# Audio recording parameters
RATE = 16000
CHUNK = int(RATE / 10)  # 100ms
LISTENING = False

# Audio string for recording purpose
audio_str = ""

# Variable to be used in auto language detection
manager = Manager()
listenSpeech_dict = manager.dict()
stt_client_dict = manager.dict()

class bcolors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'


class HumanGreeterModule(ALModule):
    """ A simple module able to react
    to facedetection events

    """
    def __init__(self, name):
        ALModule.__init__(self, name)
        # No need for IP and port here because
        # we have our Python broker connected to NAOqi broker

        # Subscribe to the FaceDetected event:
        global memory
        memory = ALProxy("ALMemory")
        self.greetingSeqNum = 0

    def onFaceDetected(self, *_args):
        """ This will be called each time a face is
        detected.
      
        """
        global customerDetected
        global conversationMode
        global create_websocket_thread
        # Unsubscribe to the event when talking,
        # to avoid repetitions

        if (customerDetected==False):
            memory.unsubscribeToEvent("PeoplePerception/PeopleDetected",
              "HumanGreeter")

            print "face detected!!"
            log.info("Face detected!!")

            #(randint(0,10))
            self.greetCustomer(self.greetingSeqNum)
            self.greetingSeqNum = self.greetingSeqNum + 1
            customerDetected = True
            conversationMode = True
            customerDetectedEvent.set()

    def subscribeToFaceDetectedEvent(self):
        log.info("Subscribe to FaceDetected event...")
        memory.subscribeToEvent("PeoplePerception/PeopleDetected","HumanGreeter","onFaceDetected")

    def greetCustomer(self,num):
        # greetingNum = (randint(0,10))
        greetingAction = projectPath+greeting_action
        
        if AUTO_LANGUAGE_DETECTION==True:
            speak(greetMainAuto)
        else:
            speak(greetMain)
            speak(greetAskLanguage)


class MicrophoneStream(object):
    """Opens a recording stream as a generator yielding the audio chunks."""
    def __init__(self,q,lang,stt_client_dict):
        self.q = q
        self.closed = True
        self._buff = queue.Queue()
        self.lang = lang
        self.stt_client_dict = stt_client_dict

    def __enter__(self):
        global TRANSCRIBING
        TRANSCRIBING = True

        self.stream_audio_thread = threading.Thread(target=self.stream_audio)
        self.stream_audio_thread.start()

        self.closed = False

        return self

    def __exit__(self, type, value, traceback):
        self.closed = True
        # Signal the generator to terminate so that the client's
        # streaming_recognize method will not block the process termination.
        # print "CLOSED"
        self._buff.put(None)
        # self._audio_interface.terminate()

    def generator(self):
        while not self.closed:
            # Use a blocking get() to ensure there's at least one chunk of
            # data, and stop iteration if the chunk is None, indicating the
            # end of the audio stream.
            chunk = self._buff.get()
            if chunk is None:
                return
            data = [chunk]

            # Now consume whatever other data's still buffered.
            while True:
                try:
                    chunk = self._buff.get(block=False)
                    if chunk is None:
                        return
                    data.append(chunk)
                except queue.Empty:
                    break

            yield b''.join(data)

    def stream_audio(self):
        
        try:
            while TRANSCRIBING:

                data = self.q.recv()
                self._buff.put(data)
                # time.sleep(0.001)
                if data==None:
                    self.closed = True
                    break
        except EOFError:
            data = None

        self.lang+": stream_audio ENDED"
    

    # def stream_audio(self):
    #     global TRANSCRIBING

    #     try:
    #         stt_client.customer_last_spoken_time = datetime.datetime.now()
    #         i=0
    #         while TRANSCRIBING:
    #             # print self.lang+": "+str((datetime.datetime.now()-stt_client.customer_last_spoken_time).total_seconds())+" | "+str(stt_client.customer_spoken)

    #             # if TRANSCRIBING==False:
    #             #     break

    #             # if (stt_client_dict["customer_spoken"]==True and (datetime.datetime.now()-stt_client.customer_last_spoken_time).total_seconds()>=STT_PAUSE_TIME):
    #             if (stt_client.customer_spoken==True and (datetime.datetime.now()-stt_client.customer_last_spoken_time).total_seconds()>=STT_PAUSE_TIME):
    #                 # print self.lang+" REACH MAX RECOGNITION TIME"
    #                 TRANSCRIBING = False
    #                 self._buff.put(None)
    #                 self.closed = True
    #                 # break
                
    #             elif((datetime.datetime.now()-stt_client.customer_last_spoken_time).total_seconds()<=STT_TIMEOUT):
    #                 self._buff.put(self.q.recv())

    #             else:
    #                 # print self.lang+" EMPTYYYYY"
    #                 TRANSCRIBING = False
    #                 self.closed = True
    #                 if stt_client.customer_spoken==False:
    #                     log.debug("Customer did not speak anything...")
    #                     print "debug: "+bcolors.WARNING+"Customer did not speak anything"+bcolors.ENDC
    #                     self._buff.put(None)
    #                     stt_client.question_text = ""
    #                     STTEndTime = time.time()
    #                 # break
    #         # print self.lang+" STREAM AUDIO ENDED"

    #     except Exception, e:
    #         print "Error in streaming audio: "+str(e)
    #         log.error("Error in streaming audio: "+str(e))

class SpeechToText():

    question_text = ""
    connection_monitor_thread = None
    get_transcription_thread = None
    
    def __init__(self):

        log.info("Initiating STT Client...")
        print "Info: "+bcolors.WARNING+"Initiating STT Client..."+bcolors.ENDC

        global isSTTClientOpened
        global exceptionDuringWebSocketInit

        self.client = None
        
        if self.connection_monitor_thread == None:
            
            self.connection_monitor_thread = threading.Thread(target=self.connection_monitor_function)
            self.connection_monitor_thread.start()

        initialConnectionCheck.wait()
        initialConnectionCheck.clear()
        
        isSTTClientOpened = True
        exceptionDuringWebSocketInit = False

        sttInitializedEvent.set()

        log.info("STT Client initiated...")
        print "Info: "+bcolors.WARNING+"STT Client initiated..."+bcolors.ENDC

    def listenSpeechAuto(self,q,lang,listenSpeech_dict,stt_client_dict):
        
        global audio_str
        global isConnectionError
        global isProgramError

        language_code = lang  # a BCP-47 language tag
        audio_str = ""
        self.customer_spoken = False
        self.question_text = ""
        self.isResultFinal = False
        self.customer_last_spoken_time = datetime.datetime.now()
        self.confidence = 0
        self.STTEndTime = time.time()
        self.STTStartTime = 0.0
        self.STTInitiateTime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        self.STTTimeTaken = 0
        # self.STTStreamingEvent = threading.Event()
        listenSpeech_dict[language_code] = [self.question_text,self.confidence,self.STTStartTime,self.STTEndTime,self.STTInitiateTime]

        try:
            self.client = speech.SpeechClient()
            config = types.RecognitionConfig(
                encoding=enums.RecognitionConfig.AudioEncoding.LINEAR16,
                sample_rate_hertz=RATE,
                language_code=language_code)
            streaming_config = types.StreamingRecognitionConfig(
                config=config,
                single_utterance=True, # pause during speech
                interim_results=True)
            with MicrophoneStream(q,language_code,stt_client_dict) as stream:
                audio_generator = stream.generator()
                requests = (types.StreamingRecognizeRequest(audio_content=content)
                    for content in audio_generator)
                responses = self.client.streaming_recognize(streaming_config, requests)
                # self.getTranscription(responses,language_code,stt_client_dict)
                try:
                    # print "HERE 2"
                    stt_client_dict[language_code] = True
                    for response in responses:
                        for result in response.results:
                            alternatives = result.alternatives
                            for alternative in alternatives:
                                self.STTStartTime = time.time()
                                self.customer_last_spoken_time = datetime.datetime.now()
                                stt_client_dict["customer_last_spoken_time"] = self.customer_last_spoken_time
                                self.customer_spoken = True
                                stt_client_dict["customer_spoken"] = True
                                self.confidence = alternative.confidence
                                self.question_text = alternative.transcript
                                self.customer_spoken = True
                                self.customer_last_spoken_time = datetime.datetime.now()
                except Exception, e:
                    self.question_text = str(e)
            
            print "Transcription ("+language_code+"): " + bcolors.OKBLUE+self.question_text.encode('utf8')+ bcolors.ENDC
            print "STT confidence: " + bcolors.WARNING+str(self.confidence)+ bcolors.ENDC
            
            listenSpeech_dict[language_code] = [self.question_text,self.confidence,self.STTStartTime,self.STTEndTime,self.STTInitiateTime]

                   
        except Exception, e:
            speak(google_stt_cred_err,"error",e)
            if isConnectionError==False:
                isProgramError = True
        # print lang+": listenSpeech ENDED"
        del self.client    

    def listenSpeech(self,q,lang,listenSpeech_dict,stt_client_dict):
        
        global audio_str
        global isConnectionError
        global isProgramError

        language_code = lang  # a BCP-47 language tag
        audio_str = ""
        self.customer_spoken = False
        self.question_text = ""
        self.isResultFinal = False
        self.customer_last_spoken_time = datetime.datetime.now()
        self.confidence = 0
        self.STTEndTime = time.time()
        self.STTStartTime = 0.0
        self.STTInitiateTime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        self.STTTimeTaken = 0
        # self.STTStreamingEvent = threading.Event()
        listenSpeech_dict[language_code] = [self.question_text,self.confidence,self.STTStartTime,self.STTEndTime,self.STTInitiateTime]

        try:
            self.client = speech.SpeechClient()
            config = types.RecognitionConfig(
                encoding=enums.RecognitionConfig.AudioEncoding.LINEAR16,
                sample_rate_hertz=RATE,
                language_code=language_code)
            streaming_config = types.StreamingRecognitionConfig(
                config=config,
                single_utterance=False, # pause during speech
                interim_results=True)
            with MicrophoneStream(q,language_code,stt_client_dict) as stream:
                audio_generator = stream.generator()
                requests = (types.StreamingRecognizeRequest(audio_content=content)
                    for content in audio_generator)
                responses = self.client.streaming_recognize(streaming_config, requests)
                self.getTranscription(responses,language_code,stt_client_dict)
                # self.getTranscription_thread = threading.Thread(target=self.getTranscription, args=(responses,language_code,))
                # self.getTranscription_thread.start()
                # self.callGoogleSTT_thread = threading.Thread(target=self.callGoogleSTT, args=(audio_generator,streaming_config,language_code,))
                # self.callGoogleSTT_thread.start()

            # if self.callGoogleSTT_thread != None:    
            #     self.callGoogleSTT_thread.join(2)
            #     self.callGoogleSTT_thread = None
            # self.STTStreamingEvent.wait()
            # self.STTStreamingEvent.clear()
            
            print "Transcription ("+language_code+"): " + bcolors.OKBLUE+self.question_text.encode('utf8')+ bcolors.ENDC
            print "STT Time Taken: " + bcolors.WARNING+str(self.STTTimeTaken)+ bcolors.ENDC
            print "STT confidence: " + bcolors.WARNING+str(self.confidence)+ bcolors.ENDC
            listenSpeech_dict[language_code] = [self.question_text,self.confidence,self.STTStartTime,self.STTEndTime,self.STTInitiateTime]

                   
        except Exception, e:
            speak(google_stt_cred_err,"error",e)
            if isConnectionError==False:
                isProgramError = True
        # print lang+": listenSpeech ENDED"
        del self.client

    def callGoogleSTT(self,audio_generator,streaming_config,language_code):
        requests = (types.StreamingRecognizeRequest(audio_content=content)
                    for content in audio_generator)
        responses = self.client.streaming_recognize(streaming_config, requests)
        self.getTranscription(responses,language_code)

    def getTranscription(self, responses, lang, stt_client_dict):
        global TRANSCRIBING
        global STTInitiateTime
        global STTStartTime
        global STTEndTime
        
        num_chars_printed = 0
        STTInitiateTime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

        try:
            stt_client_dict[lang] = True
            for response in responses:
                
                if not response.results:
                    continue

                # There could be multiple results in each response.
                result = response.results[0]
                if not result.alternatives:
                    continue

                # Display the transcription of the top alternative.
                transcript = result.alternatives[0].transcript
                self.confidence = result.alternatives[0].confidence

                # Display interim results, but with a carriage return at the end of the
                # line, so subsequent lines will overwrite them.
                #
                # If the previous result was longer than this one, we need to print
                # some extra spaces to overwrite the previous result
                overwrite_chars = ' ' * (num_chars_printed - len(transcript))

                if not result.is_final:

                    self.STTStartTime = time.time()
                    # sys.stdout.write(transcript + overwrite_chars + '\r')
                    # sys.stdout.flush()
                    num_chars_printed = len(transcript)
                    self.customer_last_spoken_time = datetime.datetime.now()
                    stt_client_dict["customer_last_spoken_time"] = self.customer_last_spoken_time
                    self.customer_spoken = True
                    stt_client_dict["customer_spoken"] = True
                    # print lang+": CUSTOMER SPOKEN"
                    self.question_text = (transcript + overwrite_chars)

                else:
                    self.STTEndTime = time.time()
                    self.STTTimeTaken = str(float(round((self.STTEndTime - self.STTStartTime),3)))
                    self.question_text = (transcript + overwrite_chars)
                    # print "Transcription ("+lang+"): " + bcolors.OKBLUE+self.question_text.encode('utf8')+ bcolors.ENDC
                    # print "STT Time Taken: " + bcolors.WARNING+STTTimeTaken+ bcolors.ENDC
                    num_chars_printed = 0
                    TRANSCRIBING = False
                    self.isResultFinal = True
                    break

            if self.isResultFinal==False:
                self.STTEndTime = time.time()
                self.STTTimeTaken = str(float(round((self.STTEndTime - self.STTStartTime),3)))
                    
        except Exception, e:
            print "Transcription ("+lang+"): " + bcolors.OKBLUE+str("Warning: Transcription error: ")+str(e)+bcolors.ENDC
            log.error("Transcription error: "+str(e))
            self.question_text = "dummymessage"

            TRANSCRIBING = False
            
        # try:
        #     sttEndTriggerEvent.set()
        # except:
        #     pass
        # print lang+": getTranscription ENDED"


    def connection_monitor_function(self):
        global LISTENING
        global isConnectionError
        global customerDetected
        global isProgramError

        conn_retry = 0
        firstCheckSiteFail = False

        try:
            googleIP = socket.gethostbyname(ConnectionCheckSite)
        except Exception, e:
            firstCheckSiteFail = True
            log.error("First check site failed.:"+str(e))
        
        if firstCheckSiteFail==True:
            try:
                googleIP = socket.gethostbyname(ConnectionCheckSite2)
                firstCheckSiteFail = False
            except Exception, e:
                isProgramError = True 
                LISTENING = False
                # tts_client.say("Network problem detected")
                print "Answer: "+bcolors.OKGREEN+network_err_en+ bcolors.ENDC
                log.error("Connection initialization failed:"+str(e))
        initialConnectionCheck.set()

        if isConnectionError == False: 
            log.debug("Started connection monitoring thread")
            checkNow = 0

            while True:
                try:
                    # if ((checkNow % 10)==0):
                    #     socket.create_connection((googleIP, 80), 2)
                    #     # urllib2.urlopen('http://'+googleIP)
                    #     # log.info("Connection OK")
                    #     conn_retry=0
                    # checkNow = checkNow + 1
                    socket.create_connection((googleIP, 80), 2)
                    isConnectionError = False
                    conn_retry = 0
                    time.sleep(CONNECTION_MONITOR_INTERVAL)
                except:
                    conn_retry = conn_retry + 1
                    LISTENING = False
                    print "Retry atempt: "+str(conn_retry)
                    
                    if (conn_retry<5):
                        print "Answer: "+bcolors.OKGREEN+network_err_en+" Trying to reconnect."+ bcolors.ENDC
                        # speak(network_err+" Trying to reconnect.")
                        time.sleep(CONNECTION_RETRY_ATTEMP_DELAY)

                    elif (conn_retry==5):
                        isConnectionError = True
                        try:
                            sttEndTriggerEvent.set()
                        except:
                            pass
                        if vmachine==False:
                            customerDetected = False

                        speak(network_err,"error")
                        time.sleep(CONNECTION_RETRY_ATTEMP_SLEEP)
                    
                    else:
                        speak(network_unavailable,"error")
                        isProgramError = True             
                        break       
        
        log.debug("Exiting connection monitoring thread")


class RobotHandlerModule():
    
    # conversationEndTime = 0.0

    def __init__(self):
        print "RobotHandler initialized"

        if vmachine == False:
            # Make sure robot is stiff
            motionProxy.stiffnessInterpolation("Body", 1.0, 1.0)

            # Make sure robot is not recording using his built in microphone
            # aurProxy.stopMicrophonesRecording();

            # Make sure robot arms is not colliding with himself
            motionProxy.setCollisionProtectionEnabled("Arms", True)

            # Awareness settings
            awarenessProxy.setEngagementMode(EngagementMode)
            awarenessProxy.setStimulusDetectionEnabled("Sound",True)
            awarenessProxy.setStimulusDetectionEnabled("Movement",True)
            awarenessProxy.setStimulusDetectionEnabled("Touch",False)
            awarenessProxy.setStimulusDetectionEnabled("People",True)
            awarenessProxy.setParameter("NobodyFoundTimeOut",NobodyFoundTimeOut)
            awarenessProxy.setParameter("MaxDistanceFullyEngaged",MaxDistanceFullyEngaged)
            awarenessProxy.setParameter("MaxDistanceNotFullyEngaged",MaxDistanceNotFullyEngaged)
            awarenessProxy.setParameter("MinTimeTracking",MinTimeTracking)
            awarenessProxy.setParameter("MaxHumanSearchTime",MaxHumanSearchTime)
            awarenessProxy.setParameter("HeadThreshold",HeadThreshold)

            perceptionProxy.setMaximumDetectionRange(MaximumDetectionRange)
            # perceptionProxy.setFastModeEnabled(False)

            awarenessProxy.startAwareness()

    
    def transcriptionMapper(self,transcript):
        mappedTranscript = transcript.rstrip()

        return mappedTranscript

    
    def initiateAPISession(self,APIUrl):
        #log
        successful = False
        
        initRequestId = str(uuid.uuid4())
        initConversationId = str(uuid.uuid4())

        headers = {'content-type': 'application/json', 'Authorization': AnswerAuthentication}
        requestText = "initiate"

        payload={"question": requestText, "conversationId": initConversationId, "deviceType": "robot", "deviceId": deviceId, "branchId": branchId, "id":initRequestId, "locale":"en-us", "language":speechLangCode_Conversation}
        payload=json.dumps(payload)
        try:
            r=session.post(APIUrl, data=payload, headers=headers, verify=False)
            successful = True
        except requests.exceptions.ConnectionError, ex:
            log.error("Answer API requests error: " + str(ex))


    def GetResponseFromAPI(self,APIUrl, **kwargs):
        #log
        conversationLogText = "Conversation Time taken :"
        log.debug("getResponseFromAPI: "+APIUrl)
        
        global isConnectionError
        global isProgramError
        global QMSTrigger
        global conversationTimeTaken
        global conversationEndTime
        global detectLanguageEndTime

        resp_obj = namedtuple("resp_obj", ["id", "action", "text", "error"])
        successful = False
        resp_error = False
        
        #Get response from API in Bluemix    
        requestId = str(uuid.uuid4())

        headers = {'content-type': 'application/json', 'Authorization': AnswerAuthentication}

        
        requestText = kwargs.get('question', None)
        requestText = self.transcriptionMapper(requestText)

        #log
        conversationStartTime = time.time()
        if detectLanguageEndTime==None:
            CPPTime1 = str(float(round((conversationStartTime - listenSpeech_dict[speechLangCode_GoogleSTT][3]),3)))
        else:
            CPPTime1 = str(float(round((conversationStartTime - detectLanguageEndTime),3)))
            detectLanguageEndTime = None
        print "STT End to Conversation start:"+bcolors.FAIL+CPPTime1+ bcolors.ENDC
        CSVWriter.CSV_CPP_TIME_1 = CPPTime1

        payload={"question": requestText, "conversationId": conversationId, "deviceType": "robot", "deviceId": deviceId, "branchId": branchId, "id":requestId, "locale":"en-us", "language":speechLangCode_Conversation}
        payload=json.dumps(payload)
        
        if(QMSTrigger==True):
            try:
                managerProxy.post.runBehavior(projectPath+thinking_action)
            except Exception, e:
                log.error("Error loading QMS thinking action: " + str(e))
                pass
            try:
                r=session.post(APIUrl, data=payload, headers=headers, verify=False)
                if(managerProxy.isBehaviorRunning(projectPath+thinking_action)): 
                    managerProxy.stopBehavior(projectPath+thinking_action)
                successful = True
                QMSTrigger = False
                conversationLogText = "QMS Time taken :"
                
            except Exception, ex:
                log.error("Answer API requests error: " + str(ex))
        else:
            try:
                r=session.post(APIUrl, data=payload, headers=headers, verify=False)
                successful = True
            except Exception, ex:
                log.error("Answer API requests error: " + str(ex))
        
        

        #log
        conversationEndTime = time.time()
        conversationTimeTaken = str(float(round((conversationEndTime - conversationStartTime),3)))
        print conversationLogText+bcolors.WARNING+conversationTimeTaken+bcolors.ENDC
        # print "Roundtrip Time taken: "+str(float(round((conversationEndTime - stt_client.sttStartTime),3)))
        log.info(conversationLogText+conversationTimeTaken)
        

        #Decode the JSON response data
        if successful == True:
            try:
                json_obj = r.json(strict=False)
                resp_text = str(json_obj['responseText'].encode('utf-8'))
                resp_action = str(json_obj['actions'][0]['answers'])    
            except Exception, e:
                if (r.status_code == 401):
                    log.error("Error code:"+str(r.status_code)+" | Details"+r.content)
                    resp_text = bluemix_unauthenticated
                    resp_error = True
                    isProgramError = True

                else:
                    log.error("Exception while decoding Response JSON Object"+str(e))
                    log.error("Error code: "+str(r.status_code)+" | Details: "+r.content)
                    if (r.status_code != 200):
                        resp_text = bluemix_err1
                        resp_error = True
                        # conversationEndTrigger = True
                        isProgramError = True
                    else:
                        resp_text = bluemix_err2
                        resp_error = True
                resp_action = "hlb_ani_ans"
        else:
            resp_text = bluemix_err3
            resp_action = "hlb_ani_ans"
            resp_error = True
            isProgramError = True
        
        global lastResponseFromAPI
        lastResponseFromAPI = resp_text
        resp = resp_obj(resp_id,resp_action,resp_text,resp_error)
        return resp    

    def detectLanguage(self,listenSpeech_dict):
        global speechLangCode_Conversation
        global speechLangCode_GoogleSTT
        global speechLangDetected
        global conversationEndTrigger
        global STTEndTime
        global detectLanguageEndTime
        global speechLangIndecisive

        confidence_array = [listenSpeech_dict["en-US"][1],listenSpeech_dict["ms-MY"][1],listenSpeech_dict["cmn-Hans-CN"][1]]
        highest_confidence = max(confidence_array)
        speechLangIndecisive = False

        if highest_confidence<CONFIDENCE_THRESHOLD:
            speechLangIndecisive = True

        elif highest_confidence==listenSpeech_dict["en-US"][1]: 
            if listenSpeech_dict["en-US"][0].encode('utf-8') == "":
                speechLangIndecisive = True
            else:
                log.info("Customer speak English")
                print "debug: "+bcolors.WARNING+"Conversation language set to English"+bcolors.ENDC
                speechLangCode_Conversation = "en"
                speechLangCode_GoogleSTT = "en-US"
                ttsProxy.setLanguage("English")

        elif highest_confidence==listenSpeech_dict["ms-MY"][1]:
            if listenSpeech_dict["ms-MY"][0].encode('utf-8') == "":
                speechLangIndecisive = True
            else:
                log.info("Customer speak Malay")
                print "debug: "+bcolors.WARNING+"Conversation language set to Malay"+bcolors.ENDC
                speechLangCode_Conversation = "ms"
                speechLangCode_GoogleSTT = "ms-MY"
                ttsProxy.setParameter("speed", 70)
                ttsProxy.setLanguage(DEFAULT_LANGUAGE)
        else:
            if listenSpeech_dict["cmn-Hans-CN"][0].encode('utf-8') == "":
                speechLangIndecisive = True
            else:
                log.info("Customer speak Mandarin")
                print "debug: "+bcolors.WARNING+"Conversation language set to Mandarin"+bcolors.ENDC
                speechLangCode_Conversation = "zh-CN"
                speechLangCode_GoogleSTT = "cmn-Hans-CN"
                ttsProxy.setLanguage("Chinese")

        
        STTEndTime = listenSpeech_dict[speechLangCode_GoogleSTT][3]        
        detectLanguageEnd = time.time()
        STTStartTimeArr = [listenSpeech_dict["en-US"][2],listenSpeech_dict["ms-MY"][2],listenSpeech_dict["cmn-Hans-CN"][2]]
        try:
            firstSTTStartTime = min(i for i in STTStartTimeArr if i > 0)
            # firstSTTStartTime = listenSpeech_dict[speechLangCode_GoogleSTT][2]
            detectLanguageTime = str(float(round((detectLanguageEnd - firstSTTStartTime),3)))
        except:
            detectLanguageTime = "N/A"
        print "Detect Language Time: "+bcolors.WARNING+str(detectLanguageTime)+bcolors.ENDC

        if speechLangIndecisive==False:
            speechLangDetected = True

            CSVWriter.CSV_USER_SPEECH = listenSpeech_dict[speechLangCode_GoogleSTT][0].encode('utf-8')
            if speechLangCode_GoogleSTT=="ms-MY":
                CSVWriter.CSV_SPEECH_LANGUAGE = "Malay"
            elif speechLangCode_GoogleSTT=="en-US":
                CSVWriter.CSV_SPEECH_LANGUAGE = "English"
            else:
                CSVWriter.CSV_SPEECH_LANGUAGE = "Chinese"
            CSVWriter.CSV_STT_START_TIME = listenSpeech_dict[speechLangCode_GoogleSTT][4]
            CSVWriter.CSV_STT_TIME_TAKEN = detectLanguageTime
            CSVWriter.CSV_DETECT_LANGUAGE = "True"
        else:
            CSVWriter.CSV_USER_SPEECH = "Language indecisive"
            CSVWriter.CSV_STT_START_TIME = listenSpeech_dict[speechLangCode_GoogleSTT][4]
            CSVWriter.CSV_STT_TIME_TAKEN = detectLanguageTime
            CSVWriter.CSV_DETECT_LANGUAGE = "True"
            CSVWriter.CSV_SPEECH_LANGUAGE = "N/A"
            pass

        detectLanguageEndTime = time.time()

        if CSVWriter.CSV_USER_SPEECH == "Language indecisive":
            AutoCSVWriter.CSV_SPEECH_DETECTED_LANGUAGE = "Language indecisive"
        else:
            AutoCSVWriter.CSV_SPEECH_DETECTED_LANGUAGE = CSVWriter.CSV_SPEECH_LANGUAGE

        AutoCSVWriter.CSV_TRANSCRIPTION = listenSpeech_dict["en-US"][0].encode('utf-8')
        AutoCSVWriter.CSV_SPEECH_STT_LANGUAGE = "English"
        AutoCSVWriter.CSV_CONFIDENCE_LEVEL = str(listenSpeech_dict["en-US"][1])
        AutoCSVWriter.write()

        AutoCSVWriter.CSV_TRANSCRIPTION = listenSpeech_dict["ms-MY"][0].encode('utf-8')
        AutoCSVWriter.CSV_SPEECH_STT_LANGUAGE = "Malay"
        AutoCSVWriter.CSV_CONFIDENCE_LEVEL = str(listenSpeech_dict["ms-MY"][1])
        AutoCSVWriter.write()

        AutoCSVWriter.CSV_TRANSCRIPTION = listenSpeech_dict["cmn-Hans-CN"][0].encode('utf-8')
        AutoCSVWriter.CSV_SPEECH_STT_LANGUAGE = "Chinese"
        AutoCSVWriter.CSV_CONFIDENCE_LEVEL = str(listenSpeech_dict["cmn-Hans-CN"][1])
        AutoCSVWriter.write()

        AutoCSVWriter.reset()


    def promptLanguage(self, question_text):
        global speechLangCode_Conversation
        global speechLangCode_GoogleSTT
        global speechLangDetected
        global conversationEndTime
        global speechLangIndecisive

        if any(x in question_text.lower() for x in malay_keyword):
            conversationEndTime = time.time()
            speechLangCode_Conversation = "ms"
            speechLangCode_GoogleSTT = "ms-MY"
            ttsProxy.setParameter("speed", 70)
            ttsProxy.setLanguage(DEFAULT_LANGUAGE)
            # ttsProxy.setLanguage("Chinese")
            speechLangDetected = True
            speechLangIndecisive = False
            print "debug: "+bcolors.WARNING+"Conversation language set to Malay"+bcolors.ENDC
            self.secondaryGreeting(greetMalay)

        elif ("english" in question_text.lower()):
            conversationEndTime = time.time()
            speechLangCode_Conversation = "en"
            speechLangCode_GoogleSTT = "en-US"
            ttsProxy.setLanguage("English")
            speechLangDetected = True
            speechLangIndecisive = False
            print "debug: "+bcolors.WARNING+"Conversation language set to English"+bcolors.ENDC
            self.secondaryGreeting(greetEnglish)

        elif any(x in question_text.lower() for x in mandarin_keyword):
            conversationEndTime = time.time()
            speechLangCode_Conversation = "zh-CN"
            speechLangCode_GoogleSTT = "cmn-Hans-CN"
            ttsProxy.setLanguage("Chinese")
            speechLangDetected = True
            speechLangIndecisive = False
            print "debug: "+bcolors.WARNING+"Conversation language set to Chinese"+bcolors.ENDC
            self.secondaryGreeting(greetMandarin)

        else:
            conversationEndTime = time.time()
            print "debug: "+bcolors.WARNING+"Fail to detect conversation language"+bcolors.ENDC
            # speak(lang_selection_err+greetAskLanguage)
            CSVWriter.CSV_STT_TIME_TAKEN = TTSTimeTaken
            CSVWriter.CSV_ROBOT_RESPONSE = "Fail to detect conversation language"


    def secondaryGreeting(self,speech):
        #whatTime is based on greeting defined in greetings.py
        currentTime = datetime.datetime.now()
        phrase = ""
        if currentTime.hour < 12:
            greetTime = 0
        elif 12 <= currentTime.hour < 18:
            greetTime = 1
        else:
            greetTime = 2

        speak(speech[greetTime])

        CSVWriter.CSV_TTS_TIME = TTSTimeTaken
        CSVWriter.CSV_ROBOT_RESPONSE = speech[greetTime]

    def playRobot(self, speech):
        global conversationEndTrigger
        global stt_client
        global QMSTrigger
        global isProgramError

        # Request response from API in Bluemix    
        resp = self.GetResponseFromAPI(AnswerAPIurl, question=speech)
        
        CSVWriter.CSV_CONVERSATION_TIME = conversationTimeTaken

        if (isinstance(resp.action, list)):
            log.debug("Action: "+projectPath+resp.action[0])
            ttsaProxy("^start("+projectPath+resp.action[0]+") "+resp.text+" ^stop("+projectPath+resp.action[0]+")")

        else:
            
            log.debug("Action: "+projectPath+resp.action)

            if resp.error==True:
                speak(resp.text,"error")
                CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                CSVWriter.CSV_TOTAL_TIME = roundtripTime
                CSVWriter.CSV_ROBOT_RESPONSE = resp.text[0]
                CSVWriter.CSV_CONVERSATION_ID = conversationId

            elif (resp.action=="hlb_ani_end"): #end of conversation
                speak(resp.text)
                CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                CSVWriter.CSV_TOTAL_TIME = roundtripTime
                CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                CSVWriter.CSV_CONVERSATION_ID = conversationId

                conversationEndTrigger = True

            elif (resp.action=="hlb_ani_qms"): #QMS Trigger
                speak(resp.text)
                CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                CSVWriter.CSV_TOTAL_TIME = roundtripTime
                CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                CSVWriter.CSV_CONVERSATION_ID = conversationId

                QMSTrigger = True
            
            elif (resp.action=="hlb_ani_selfie"): #selfie

                stt_client.isStreaming = False
                log.debug("Capturing picture...")
                speak(resp.text,"selfie")
                CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                CSVWriter.CSV_TOTAL_TIME = roundtripTime
                CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                CSVWriter.CSV_CONVERSATION_ID = conversationId

                if(isSTTClientOpened==True):   
                    resp = self.GetResponseFromAPI(AnswerAPIurl, question="re-take selfie")
                    log.debug("Action: "+projectPath+resp.action)
                    speak(resp.text)
                    CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                    if resp.error==True:
                        CSVWriter.CSV_ROBOT_RESPONSE = resp.text[0]
                    else:
                        CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                    CSVWriter.CSV_CONVERSATION_ID = conversationId
                    

            elif (resp.action=="GangnamStyle" or resp.action=="CaravanStyle" or resp.action=="danceCNY"): #modern dance  

                try:
                    stt_client.isStreaming = False
                    log.debug("Initiating Modern Dance...")
                    speak(resp.text)
                    CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                    CSVWriter.CSV_TOTAL_TIME = roundtripTime
                    CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                    CSVWriter.CSV_CONVERSATION_ID = conversationId

                    if (resp.action=="CaravanStyle"):
                        managerProxy.runBehavior("caravan-palace")
                    else:    
                        managerProxy.runBehavior(projectPath+resp.action)
                    
                    if(isSTTClientOpened==True):            
                        resp = self.GetResponseFromAPI(AnswerAPIurl, question="show-dance-end")
                        log.debug("Action: "+projectPath+resp.action)
                        speak(resp.text) 
                        CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                        if resp.error==True:
                            CSVWriter.CSV_ROBOT_RESPONSE = resp.text[0]
                        else:
                            CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                        CSVWriter.CSV_CONVERSATION_ID = conversationId

                        # conversationEndTrigger = True

                except Exception, e:
                    speak(invalid_action_err,"error",e)
                    
                    resp = self.GetResponseFromAPI(AnswerAPIurl, question="show-dance-fail")
                    log.debug("Action: "+projectPath+resp.action)
                    speak(resp.text)
                    CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                    if resp.error==True:
                        CSVWriter.CSV_ROBOT_RESPONSE = resp.text[0]
                    else:
                        CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                    CSVWriter.CSV_CONVERSATION_ID = conversationId

            else:
                speak(resp.text)
                CSVWriter.CSV_TTS_TIME = TTSTimeTaken
                CSVWriter.CSV_TOTAL_TIME = roundtripTime
                CSVWriter.CSV_ROBOT_RESPONSE = resp.text
                CSVWriter.CSV_CONVERSATION_ID = conversationId

            if (resp.action=="hlb_ani_print"):
                CSVWriter.CSV_RESPONSE_TYPE = "QMS"

class CSVWriterHandler():
    def __init__(self):

        #CSV Logging Variable
        self.CSV_USER_SPEECH = "N/A"
        self.CSV_SPEECH_LANGUAGE = "N/A"
        self.CSV_STT_START_TIME = "N/A"
        self.CSV_STT_TIME_TAKEN = "N/A"
        self.CSV_DETECT_LANGUAGE = "N/A"
        self.CSV_CPP_TIME_1 = "N/A"
        self.CSV_CONVERSATION_TIME = "N/A"
        self.CSV_CPP_TIME_2 = "N/A"
        self.CSV_TTS_TIME = "N/A"
        self.CSV_TOTAL_TIME = "N/A"
        self.CSV_ROBOT_RESPONSE = "NonQMS"
        self.CSV_RESPONSE_TYPE = "N/A"
        self.CSV_CONVERSATION_ID = "N/A"
        self.CSV_DEVICE_ID = deviceId
        self.CSV_BRANCH_ID = branchId

        if CSV_REPORT == True:
            if os.path.exists(logPath+logname+".csv"):
                # with codecs.open(logPath+logname+".csv", "a") as csvfile:
                #     csvfile.write("\n")
                pass
            else:
                with codecs.open(logPath+logname+".csv", "a") as csvfile:
                    csvfile.write("User_Speech,Language,STT_Start_Time,STT_Time,Detect_Language_Time,CPP_Time_1,Conversation_Time,CPP_Time_2,TTS_Time,Total_Time,Robot_Response,Response_Type,Conversation_ID,Device_ID,Branch_ID\n")

    def reset(self):
        #CSV Logging Variable
        self.CSV_USER_SPEECH = "N/A"
        self.CSV_SPEECH_LANGUAGE = "N/A"
        self.CSV_STT_START_TIME = "N/A"
        self.CSV_STT_TIME_TAKEN = "N/A"
        self.CSV_DETECT_LANGUAGE = "False"
        self.CSV_CPP_TIME_1 = "N/A"
        self.CSV_CONVERSATION_TIME = "N/A"
        self.CSV_CPP_TIME_2 = "N/A"
        self.CSV_TTS_TIME = "N/A"
        self.CSV_TOTAL_TIME = "N/A"
        self.CSV_ROBOT_RESPONSE = "N/A"
        self.CSV_RESPONSE_TYPE = "NonQMS"
        self.CSV_CONVERSATION_ID = "N/A"
        self.CSV_DEVICE_ID = deviceId
        self.CSV_BRANCH_ID = branchId

    def write(self):
        with codecs.open(logPath+logname+".csv", "a") as csvfile:
            csvfile.write('"'+self.CSV_USER_SPEECH+'"'+','+self.CSV_SPEECH_LANGUAGE+','+self.CSV_STT_START_TIME+','+self.CSV_STT_TIME_TAKEN+','+self.CSV_DETECT_LANGUAGE+','+self.CSV_CPP_TIME_1+','+self.CSV_CONVERSATION_TIME+','+self.CSV_CPP_TIME_2+','+self.CSV_TTS_TIME+','+self.CSV_TOTAL_TIME+','+'"'+self.CSV_ROBOT_RESPONSE+'"'+','+self.CSV_RESPONSE_TYPE+','+self.CSV_CONVERSATION_ID+','+self.CSV_DEVICE_ID+','+self.CSV_BRANCH_ID+'\n')


class AutoCSVWriterHandler():
    def __init__(self):

        #CSV Logging Variable
        self.CSV_RECORDED_SPEECH = "N/A"
        self.CSV_SPEECH_ORIGINAL_LANGUAGE = "To be filled by HLB"
        self.CSV_TRANSCRIPTION = "N/A"
        self.CSV_SPEECH_STT_LANGUAGE = "N/A"
        self.CSV_CONFIDENCE_LEVEL = "N/A"
        self.CSV_SPEECH_DETECTED_LANGUAGE = "N/A"

        if CSV_REPORT == True:
            if os.path.exists(logPath+"LanguageDetection"+logname+".csv"):
                # with codecs.open(logPath+logname+".csv", "a") as csvfile:
                #     csvfile.write("\n")
                pass
            else:
                with codecs.open(logPath+"LanguageDetection"+logname+".csv", "a") as csvfile:
                    csvfile.write("Speech_Recording,Original_Language,Transcription,STT_Language,Confidence_Level,Detected_Language\n")

    def reset(self):
        #CSV Logging Variable
        self.CSV_RECORDED_SPEECH = "N/A"
        self.CSV_SPEECH_ORIGINAL_LANGUAGE = "To be filled by HLB"
        self.CSV_TRANSCRIPTION = "N/A"
        self.CSV_SPEECH_STT_LANGUAGE = "N/A"
        self.CSV_CONFIDENCE_LEVEL = "N/A"
        self.CSV_SPEECH_DETECTED_LANGUAGE = "N/A"

    def write(self):
        with codecs.open(logPath+"LanguageDetection"+logname+".csv", "a") as csvfile:
            csvfile.write(self.CSV_RECORDED_SPEECH+','+self.CSV_SPEECH_ORIGINAL_LANGUAGE+','+'"'+self.CSV_TRANSCRIPTION+'"'+','+self.CSV_SPEECH_STT_LANGUAGE+','+self.CSV_CONFIDENCE_LEVEL+','+self.CSV_SPEECH_DETECTED_LANGUAGE+'\n')

def createSTTClient():
    # Check Websocket status
    # Initiate Websocket connection if websocket is not alive
    global isSTTClientOpened
    global sttInitializedEvent
    global isConnectionError
    global stt_client

    if (isSTTClientOpened==False):

        # isConnectionError = False

        while(isSTTClientOpened==False):

            stt_client = SpeechToText()
            sttInitializedEvent.wait()
            sttInitializedEvent.clear()

            if(isSTTClientOpened==False):

                if(isConnectionError==True):
                    break

                speak(google_stt_init_err)
                print "Answer: "+bcolors.OKGREEN+"I am sorry. I have problem initiating my listening module. Please wait a moment for me to reconnect"+ bcolors.ENDC
                log.error("Problem initiating websocket client...")

def record_multiple(q12,q22=None,q32=None):
        global LISTENING
        global STTEndTime
        global audio_str

        LISTENING = True
        audio_str = ""

        reccmd = "arecord -f S16_LE -r 16000 -t wav"
        # reccmd = ["arecord", "-f", "S16_LE", "-r", "16000", "-t", "raw"]
        # aupProxy.post.playFile(soundPath+"begin_bleep.wav")
        while stt_client_dict["en-US"]==False and stt_client_dict["ms-MY"]==False and stt_client_dict["cmn-Hans-CN"]==False:
            pass

        try:
            p = os.popen(reccmd)         
            # p = subprocess.Popen(reccmd, stdout=subprocess.PIPE) 
            print "Beep Delay: "+bcolors.FAIL+str(float(round((time.time() - TTSEndTime),3)))+bcolors.ENDC  
            aupProxy.post.playFile(soundPath+"begin_bleep.wav")
            
            stt_client_dict["customer_last_spoken_time"] = datetime.datetime.now()
            stt_client_dict["customer_spoken"] = False
            
            while LISTENING:
                # print str((datetime.datetime.now()-stt_client_dict["customer_last_spoken_time"]).total_seconds())

                if (stt_client_dict["customer_spoken"]==True and (datetime.datetime.now()-stt_client_dict["customer_last_spoken_time"]).total_seconds()>=STT_PAUSE_TIME):
                    q12.send(None)
                    q22.send(None)
                    q32.send(None)
                    LISTENING = False

                elif((datetime.datetime.now()-stt_client_dict["customer_last_spoken_time"]).total_seconds()<=STT_TIMEOUT):    
                    data = p.read(1024)
                    audio_str = audio_str+data
                    q12.send(data)
                    q22.send(data)
                    q32.send(data)
                else:
                    LISTENING = False
                    q12.send(None)
                    q22.send(None)
                    q32.send(None)
                    if stt_client_dict["customer_spoken"]==False:
                        log.debug("Customer did not speak anything...")
                        print "debug: "+bcolors.WARNING+ "Customer did not speak anything"+bcolors.ENDC
                        STTEndTime = time.time()
            print "RECORD ENDED"

        except Exception, e:
            print "Error in streaming audio: "+str(e)
            log.error("Error in streaming audio: "+str(e))
        aupProxy.post.playFile(soundPath+"end_bleep.wav")
        # p.kill()
        p.close()
        # print "ENDED RECORDING"

        if SAVE_RECORDING:
            recordingName = (datetime.datetime.now()).strftime("%d%m%Y_%I%M%S%p")
            log.info("Saving audio...") 
            with open("sound/"+recordingName+".wav", "w") as recordingFile:
                recordingFile.write(audio_str)  
            log.info("Audio saved...")
                
            AutoCSVWriter.CSV_RECORDED_SPEECH = recordingName+".wav"
            
        sttEndTriggerEvent.set()

def record_single(q12):
        global LISTENING
        global STTEndTime
        global audio_str

        LISTENING = True
        audio_str = ""

        reccmd = "arecord -f S16_LE -r 16000 -t raw"
        # reccmd = ["arecord", "-f", "S16_LE", "-r", "16000", "-t", "raw"]
        # aupProxy.post.playFile(soundPath+"begin_bleep.wav")
        try:
            p = os.popen(reccmd)         
            # p = subprocess.Popen(reccmd, stdout=subprocess.PIPE) 
            print "Beep Delay: "+bcolors.FAIL+str(float(round((time.time() - TTSEndTime),3)))+bcolors.ENDC  
            aupProxy.post.playFile(soundPath+"begin_bleep.wav")
            
            stt_client_dict["customer_last_spoken_time"] = datetime.datetime.now()
            stt_client_dict["customer_spoken"] = False
            
            while LISTENING:
                # print str((datetime.datetime.now()-stt_client_dict["customer_last_spoken_time"]).total_seconds())

                if (stt_client_dict["customer_spoken"]==True and (datetime.datetime.now()-stt_client_dict["customer_last_spoken_time"]).total_seconds()>=STT_PAUSE_TIME):
                    q12.send(None)
                    LISTENING = False

                elif((datetime.datetime.now()-stt_client_dict["customer_last_spoken_time"]).total_seconds()<=STT_TIMEOUT):    
                    data = p.read(1024)
                    audio_str = audio_str+data
                    q12.send(data)
                else:
                    LISTENING = False
                    q12.send(None)
                    if stt_client_dict["customer_spoken"]==False:
                        log.debug("Customer did not speak anything...")
                        print "debug: "+bcolors.WARNING+ "Customer did not speak anything"+bcolors.ENDC
                        STTEndTime = time.time()
            print "RECORD ENDED"

        except Exception, e:
            print "Error in streaming audio: "+str(e)
            log.error("Error in streaming audio: "+str(e))
        aupProxy.post.playFile(soundPath+"end_bleep.wav")
        # p.kill()
        p.close()
        # print "ENDED RECORDING"
        sttEndTriggerEvent.set()

def speak(speech,type="normal",message=""):
    
    global TTSTimeTaken
    global roundtripTime
    global TTSEndTime
    try:
        STTStartTime = listenSpeech_dict[speechLangCode_GoogleSTT][2]
    except:
        STTStartTime = time.time()

    TTSInitiateTime = time.time()
    CPPTime2 = str(float(round((TTSInitiateTime - conversationEndTime),3)))
    print "Conversation End to TTS Initiate:"+bcolors.FAIL+CPPTime2+ bcolors.ENDC
    try:
        CSVWriter.CSV_CPP_TIME_2 = CPPTime2
    except:
        pass

    try:    
        if type=="error_en":
            print "Robot: "+bcolors.OKGREEN + speech + bcolors.ENDC
            log.error("Robot: "+speech)
            if message!="":
                log.error("Error Message: "+str(message))
            if speechLangCode_GoogleSTT=="ms-MY":
                tts = gTTS(text=speech, lang='id')
                tts.save("malay-speech.wav")
                ttsaProxy.post.say("\\vol=0\\"+speech)
                TTSStartTime = time.time()
                os.system("play malay-speech.wav >/dev/null 2>&1")
            else:
                TTSStartTime = time.time()
                ttsaProxy.say(speech)

        elif type=="error":
            print "Robot: "+bcolors.OKGREEN + speech[0] + bcolors.ENDC
            log.error("Robot: "+speech[0])
            if message!="":
                log.error("Error Message: "+str(message))
            if speechLangCode_GoogleSTT=="en-US":
                TTSStartTime = time.time()
                ttsaProxy.say(speech[0])
            elif speechLangCode_GoogleSTT=="ms-MY":
                tts = gTTS(text=speech[1], lang='id')
                tts.save("malay-speech.wav")
                ttsaProxy.post.say("\\vol=0\\"+speech[1])
                TTSStartTime = time.time()
                os.system("play malay-speech.wav >/dev/null 2>&1")
            else:
                TTSStartTime = time.time()
                ttsaProxy.say(speech[2])
        
        elif type=="selfie":
            print "Robot: "+bcolors.OKGREEN + speech + bcolors.ENDC
            log.info("Robot: "+speech)
            if speechLangCode_GoogleSTT=="ms-MY":
                tts = gTTS(text=speech, lang='id')
                tts.save("malay-speech.wav")
                ttsaProxy.post.say("^start("+projectPath+selfie_action+") \\vol=0\\"+speech+" \\pau="+SELFIE_PAUSE+"\\ ^stop("+projectPath+selfie_action+")")
                TTSStartTime = time.time()
                os.system("play malay-speech.wav >/dev/null 2>&1")
                time.sleep(float(SELFIE_PAUSE)/1000)
            else:
                TTSStartTime = time.time()
                ttsaProxy.say("^start("+projectPath+selfie_action+") "+speech+" \\pau="+SELFIE_PAUSE+"\\ ^stop("+projectPath+selfie_action+")")

        else:
            print "Robot: "+bcolors.OKGREEN + speech + bcolors.ENDC
            log.info("Robot: "+speech)
            if speechLangCode_GoogleSTT=="ms-MY":
                tts = gTTS(text=speech, lang='id')
                tts.save("malay-speech.wav")
                ttsaProxy.post.say("\\vol=0\\"+speech)
                TTSStartTime = time.time()
                os.system("play malay-speech.wav >/dev/null 2>&1")
            else:
                TTSStartTime = time.time()
                ttsaProxy.say(speech)

    except Exception, e:
        log.error("Error in "+speechLangCode_GoogleSTT+"Text To Speech module")
        print "Error in "+bcolors.OKGREEN+speechLangCode_GoogleSTT+bcolors.ENDC+"Text To Speech module"
        if speechLangCode_GoogleSTT=="ms-MY":
            ttsaProxy.post.say("\\vol=0\\"+"I am unable to talk right now")
            TTSStartTime = time.time()
            os.system("play sound/malay-tts-error.wav >/dev/null 2>&1")
        else:
            pass

    TTSTimeTaken = str(float(round((TTSStartTime - TTSInitiateTime),3)))
    roundtripTime = str(float(round((TTSStartTime - STTStartTime),3)))
    TTSEndTime = time.time()

def main():
    
    """ Main entry point
  
    """
    global myBroker
    myBroker = ALBroker("myBroker",
       "0.0.0.0",   # listen to anyone
       0,           # find a free port and use it
       robotIP,     # parent broker IP
       portNumber)  # parent broker port


    # Warning: HumanGreeter must be a global variable
    # The name given to the constructor must be the name of the
    # variable
    global conversationMode
    global customerDetected
    global HumanGreeter
    global CSVWriter
    global AutoCSVWriter
    global stt_client
    global conversationId
    global isConnectionError
    global isProgramError
    global isSTTClientOpened
    global conversationEndTrigger
    global QMSTrigger
    global sttEndTrigger
    global exceptionDuringAudioStreaming
    global stt_inactivity_timeout
    global conversationStarted
    global lastResponseFromAPI
    global vmachine
    global speechLangCode_Conversation
    global speechLangCode_GoogleSTT
    global speechLangDetected
    global speechLangIndecisive

    RobotHandler = RobotHandlerModule()
    HumanGreeter = HumanGreeterModule("HumanGreeter")
    CSVWriter = CSVWriterHandler()
    AutoCSVWriter = AutoCSVWriterHandler()

    createSTTClient()

    timeout_trigger = 0
    question_text = ""
    initial_text = ""
    waitingForCustomer = False
    global customerDetectedEvent, sttEndTriggerEvent, session
    
    print "waiting for face detection"
    
    while True:

        ### FOR RUNNING IN VM WHERE HUMAN PRESENCE DETECTION IS NOT AVAILABLE ###
        if customerDetected==False and vmachine==True:
            global TTSEndTime

            conversationMode    = True
            customerDetected    = True
            speechLangDetected  = False
            speechLangCode_Conversation = "en"
            speechLangCode_GoogleSTT = "en-US"
            TTSEndTime = time.time()
        #######

        # Handle connection error
        if isProgramError==False and isConnectionError==True:
            # DO SOMETHING HERE
            # ledProxy.post.fadeRGB("FaceLeds"",green",0.0)
            # ledProxy.post.reset("FaceLeds")

            # MAKE HIS EYE ORANGE???

            if vmachine==True:
                conversationMode = True
                customerDetected = True

            waitingForCustomer = False
            conversationId = ""
            timeout_trigger = 0
            conversationEndTrigger = False
            QMSTrigger = False
            conversationStarted = False
            question_text = ""
            lastResponseFromAPI = ""
            initial_text = ""
            speechLangDetected = False
            speechLangCode_Conversation = "en"
            speechLangCode_GoogleSTT = "en-US"
            ttsProxy.setLanguage(DEFAULT_LANGUAGE)

            while isConnectionError==True and isProgramError==False:
                pass

        # Detect Customer Presence
        # Comment this part if running VM    
        if (isProgramError==False and customerDetected==False):
        
            HumanGreeter.subscribeToFaceDetectedEvent()
            customerDetectedEvent.wait()
            customerDetectedEvent.clear()
            conversationMode    = True
            customerDetected    = True
            session = requests.session()
        
        # Check if conversation ended    
        if isProgramError==False and (timeout_trigger==STT_TIMEOUT_ATTEMPT or conversationEndTrigger==True):
            
            if (conversationStarted == True):
                print bcolors.OKGREEN + "Conversation ended..." + bcolors.ENDC
                log.info("Conversation ended")
                speak(conv_end_msg,"error")
                time.sleep(SLEEP_BEFORE_FACE_DETECTION)

            else:
                print bcolors.OKGREEN + "Face detected but customer not talking to me..." + bcolors.ENDC
                log.info("Face detected but customer not talking to me...")
                # if(isSTTClientOpened==True):
                #     speak(customer_non_responsive_response)
          
            # if (isConnectionError==True):
            #     isSTTClientOpened=False

            # stt_inactivity_timeout = '-1'
            waitingForCustomer = False
            conversationMode = False
            conversationId = ""
            timeout_trigger = 0
            conversationEndTrigger = False
            QMSTrigger = False
            conversationStarted = False
            question_text = ""
            lastResponseFromAPI = ""
            initial_text = ""
            customerDetected = False
            speechLangDetected = False
            speechLangCode_Conversation = "en"
            speechLangCode_GoogleSTT = "en-US"
            ttsProxy.setLanguage(DEFAULT_LANGUAGE)

            #sleep so that robot won't immediately detect another face
            time.sleep(SLEEP_BEFORE_FACE_DETECTION)
            
            print "waiting for face detection"

            continue
        
        # Initiate conversation if human is present
        if (isProgramError==False and customerDetected==True and conversationMode==True and isConnectionError==False):
            global LISTENING
            global STT_PAUSE_TIME

            if (isSTTClientOpened==False): 
                createSTTClient()

            log.info("Conversation started...")

            # Set conversation ID to be used in API call
            if (conversationId==""):
                conversationId=str(uuid.uuid4())
                log.info("conversationId: "+conversationId)

            # Initiate speech streaming
            # While speech is streaming, stop websocket keep alive signal     
            log.info("Say something...")
            sttEndTrigger = False

            stt_client_dict["customer_spoken"] = False #customer_spoken

            if speechLangDetected==False and speechLangIndecisive==True:
                speak(lang_selection_err+greetAskLanguage)

            if speechLangDetected==False and speechLangIndecisive==False:
                # print "HERE 1"
                q11,q12 = Pipe()
                q21,q22 = Pipe()
                q31,q32 = Pipe()
                stt_client_dict["en-US"] = False
                stt_client_dict["ms-MY"] = False
                stt_client_dict["cmn-Hans-CN"] = False
                # STT_PAUSE_TIME = 5
                # recording_thread = threading.Thread(target=record, args=(q12,))
                # recording_thread.start()
                # stt_client.listenSpeech(q11,"en-US")
                listenSpeech_process1 = Process(target=stt_client.listenSpeechAuto, args=(q11,"en-US",listenSpeech_dict,stt_client_dict,))
                listenSpeech_process1.start()
                listenSpeech_process2 = Process(target=stt_client.listenSpeechAuto, args=(q21,"ms-MY",listenSpeech_dict,stt_client_dict,))
                listenSpeech_process2.start()
                listenSpeech_process3 = Process(target=stt_client.listenSpeechAuto, args=(q31,"cmn-Hans-CN",listenSpeech_dict,stt_client_dict,))
                listenSpeech_process3.start()
                recording_thread = threading.Thread(target=record_multiple, args=(q12,q22,q32,))
                recording_thread.start()
                recording_thread.join()
                listenSpeech_process1.join(STT_PAUSE_TIME+1)
                listenSpeech_process2.join(STT_PAUSE_TIME+1)
                listenSpeech_process3.join(STT_PAUSE_TIME+1)
                try:
                    listenSpeech_process1.terminate()
                    listenSpeech_process2.terminate()
                    listenSpeech_process3.terminate()
                except:
                    pass

            else:
                q11,q12 = Pipe()
                q21,q22 = Pipe()
                q31,q32 = Pipe()

                # STT_PAUSE_TIME = 1
                listenSpeech_process1 = Process(target=stt_client.listenSpeech, args=(q11,speechLangCode_GoogleSTT,listenSpeech_dict,stt_client_dict,))
                listenSpeech_process1.start()
                recording_thread = threading.Thread(target=record_single, args=(q12,))
                recording_thread.start()
                listenSpeech_process1.join(STT_TIMEOUT+1)
                try:
                    listenSpeech_process1.terminate()
                except:
                    pass

                CSVWriter.CSV_USER_SPEECH = listenSpeech_dict[speechLangCode_GoogleSTT][0].encode('utf-8')
                if speechLangIndecisive==True:
                    CSVWriter.CSV_SPEECH_LANGUAGE = "N/A"
                elif speechLangCode_GoogleSTT=="ms-MY":
                    CSVWriter.CSV_SPEECH_LANGUAGE = "Malay"
                elif speechLangCode_GoogleSTT=="en-US":
                    CSVWriter.CSV_SPEECH_LANGUAGE = "English"
                else:
                    CSVWriter.CSV_SPEECH_LANGUAGE = "Chinese"
                CSVWriter.CSV_STT_START_TIME = listenSpeech_dict[speechLangCode_GoogleSTT][4]
                CSVWriter.CSV_STT_TIME_TAKEN = str(float(round((listenSpeech_dict[speechLangCode_GoogleSTT][3] - listenSpeech_dict[speechLangCode_GoogleSTT][2]),3)))

            LISTENING = False    
            sttEndTriggerEvent.wait()
            sttEndTriggerEvent.clear()
           
            try:
                if speechLangDetected==True:
                    question_text = listenSpeech_dict[speechLangCode_GoogleSTT][0]
                    STTEndTime = listenSpeech_dict[speechLangCode_GoogleSTT][3]
                else:
                    tempstringarray = [listenSpeech_dict["en-US"][0],listenSpeech_dict["ms-MY"][0],listenSpeech_dict["cmn-Hans-CN"][0]]
                    question_text = next(s for s in tempstringarray if s)
                    STTEndTime = time.time()
            except:
                question_text = ""
                STTEndTime = time.time()
                      
        #Check if customer didnt say anything
        if (isProgramError==False and isSTTClientOpened==True and conversationMode==True and customerDetected==True and question_text=="" and conversationEndTrigger==False and isConnectionError==False):
            
            global conversationEndTime
            conversationEndTime = time.time()

            if ((conversationStarted==True) and (timeout_trigger<STT_TIMEOUT_ATTEMPT-1)):        
                speak(cust_not_speaking_msg,"error")

            if (conversationStarted==False):
                conversationEndTrigger=True

            timeout_trigger += 1
          
            continue

        #Customer said "STOP" keyword
        if ((isProgramError==True) or (conversationMode==True and customerDetected==True and "stop now" in question_text)):
            conversationEndTrigger=True
            log.info("Conversation stopped...")

            print "Answer: "+bcolors.OKGREEN+"Exiting program"+ bcolors.ENDC
            ttsProxy.setLanguage(DEFAULT_LANGUAGE)
            speak(exit_program_msg,"error")

            myBroker.shutdown()
            
            if isProgramError==True:
                os._exit(1)
            else:
                os._exit(0)

        #Play robot   
        if (isProgramError==False and isConnectionError==False and isSTTClientOpened==True and conversationMode==True and customerDetected==True and question_text!=""):
            # if(conversationStarted==False):
            #     print "HERE 1"
            #     conversationStarted = True

            timeout_trigger = 0          

            if speechLangDetected==False and speechLangIndecisive==True:
                RobotHandler.promptLanguage(listenSpeech_dict[speechLangCode_GoogleSTT][0])
                if(conversationStarted==False):
                    conversationStarted = True
            elif speechLangDetected==False:
                RobotHandler.detectLanguage(listenSpeech_dict)
                if speechLangDetected==True:
                    if(conversationStarted==False):
                        conversationStarted = True
                    RobotHandler.playRobot(listenSpeech_dict[speechLangCode_GoogleSTT][0])
                pass
            else:
                if(conversationStarted==False):
                        conversationStarted = True
                RobotHandler.playRobot(question_text)
            print("\n")

        if (CSV_REPORT==True and question_text!=""):
            CSVWriter.write()
            CSVWriter.reset()


if __name__ == "__main__":

    # speak("Initializing my listening module. Please wait for the beep.")
    time.sleep(BOOT_TIME)
  
    parser = argparse.ArgumentParser(description='NAO Robot for Hong Leong Bank')
    parser.add_argument('-e','--env', help='Answer API Environment', required=False)
    parser.add_argument('-l','--loglvl', help='Logger level', required=False)
    parser.add_argument('-o','--outlvl', help='Terminal logger level', required=False)
    parser.add_argument('-c','--conid', help='Conversation ID', required=False)
    parser.add_argument('-f','--filename', help='File Name', required=False)
    parser.add_argument('-vm','--vmachine', help='Virtual Machine', required=False)
    parser.add_argument('--pip','--pip', help='robot ip', required=False)
    parser.add_argument('--pport','--pport', help='robot port', required=False)
    args = vars(parser.parse_args())
  
  
    if args['loglvl'] is not None:
        # log.info("Setting log level to:" + args['loglvl'])
        loggerLevel = args['loglvl']

  
    if args['outlvl'] is not None:
        # log.info("Setting stdout log level to:" + args['outlvl'])
        if args['outlvl'].lower() == "none":
            stdoutLevel = "CRITICAL"
        else:
            stdoutLevel = args['outlvl']

    if args['filename'] is not None:
        filename = args['filename']+"_nao_main"
    else:    
        filename = "nao_main"

    # Running on VM flag
    if args['vmachine'] is not None:
        # global vmachine
        if args['vmachine'].lower() == "true":
            vmachine = True
        else:
            vmachine = False
    else:
        vmachine = False

    if args['conid'] is not None:
        # global conversationId
        conversationId = args['conid']

    # initialize text to speech proxy
    try:
        ttsaProxy = ALProxy("ALAnimatedSpeech", robotIP,portNumber)
        ttsProxy = ALProxy("ALTextToSpeech", robotIP,portNumber)
        ttsProxy.setLanguage(DEFAULT_LANGUAGE)
        ttsProxy.setParameter("defaultVoiceSpeed", SPEECH_SPEED)
    except Exception, e:
        print "Robot: "+bcolors.OKGREEN+robot_cred_err+ bcolors.ENDC
        print "Exception: "+bcolors.FAIL+str(e)+ bcolors.ENDC
        os.system('espeak -ven+f2 -s 140 --stdout "'+robot_cred_err+'" | aplay >/dev/null 2>&1')
        os._exit(1)

    # check whether path exist or not
    if os.path.isdir(programPath)==False:
        print "Error: "+path_cred_err
        ttsaProxy.say(path_cred_err)
        ttsaProxy.say(exit_program_msg[0])
        os._exit(1)

    # print filename
    logDate = time.strftime("%Y%m%d")
    logname = "AutoDetect_"+logDate+"_"+branchId.replace(" ", "").replace("_", "")+"_"+deviceId.replace(" ", "").replace("_", "")+"_Robot"
    try:
        log = myLog(__name__,logPath+logname+".log",loggerLevel,stdoutLevel)
    except Exception, e:
        # print "Robot: "+bcolors.OKGREEN+log_level_err+ bcolors.ENDC
        # print "Exception: "+bcolors.FAIL+str(e)+ bcolors.ENDC
        print e
        speak(log_level_err,"error_en")
        speak(exit_program_msg,"error")
        os._exit(1)

    # Announce that initial script check is successfull
    speak(BOOT_UP_MESSAGE)

    # Init proxies.
    try:
        motionProxy = ALProxy("ALMotion", robotIP, portNumber)
    except Exception, e:
        log.error("Could not create proxy to ALMotion")
        log.error("Error was: "+ str(e))

    try:
        aupProxy = ALProxy("ALAudioPlayer",robotIP,portNumber)
    except Exception, e:
        log.error("Could not create proxy to ALAudioPlayer")
        log.error("Error was: "+ str(e)) 

    try:
        ledProxy = ALProxy("ALLeds", robotIP, portNumber)
    except Exception, e:
        log.error("Could not create proxy to ALLeds")
        log.error("Error was: "+ str(e))

    try:
        managerProxy = ALProxy("ALBehaviorManager", robotIP, portNumber)
    except Exception, e:
        log.error("Could not create proxy to ALBehaviorManager")
        log.error("Error was: "+ str(e))

    try:
        awarenessProxy = ALProxy("ALBasicAwareness", robotIP, portNumber)
    except Exception, e:
        log.error("Could not create proxy to ALBasicAwareness")
        log.error("Error was: "+ str(e))

    try:
        perceptionProxy = ALProxy("ALPeoplePerception", robotIP, portNumber)
    except Exception, e:
        log.error("Could not create proxy to ALPeoplePerception")
        log.error("Error was: "+ str(e))

    
    if vmachine==False:    
        try:
            fdProxy = ALProxy("ALFaceDetection", robotIP, portNumber)
        except Exception, e:
            log.error("Could not create proxy to ALFaceDetection")
            log.error("Error was: "+ str(e))
        try:
            aurProxy = ALProxy("ALAudioRecorder",robotIP,portNumber)
            channels = (0,0,1,0)
        except Exception, e:
            log.error("Could not create proxy to ALAudioRecorder")
            log.error("Error was: "+ str(e))

        

    try: 
        main()
    except KeyboardInterrupt:
        log.info('Interrupted by user..')
        try:
            log.info("Exiting program..")
            
            exceptionDuringAudioStreaming = True
            myBroker.shutdown()
            sys.exit(0)
        except SystemExit:
            log.info("Exiting program2..")
            myBroker.shutdown()
            os._exit(0)


