// nRF24L01+ su ATTiny84
// code for data TX vers. 6b
// hardware connections:
// nrf24L01:  1 2 3  4 5 6 7
// attiny84: 14 1 6 10 9 8 7

// include ultime modifiche (data_type 1 digit)
// rispetto a BARS1 manca la gestione del period (ricezione e salvatoaggio in EPROM)
// e non è DS18b20

#include <RF24.h>
#include <Narcoleptic.h>
// #include "printf.h" //DEBUG

RF24 radio(PA3,PA7);   // attiny84: pins for nrf24l01 CSN,CE

#define tempPin A2     // TMP36 Vout connected to A2 (ATtiny pin 11)
#define radioPower 0   // EN Voltage Regulator pin is connected on pin PB0 - D0 (ATtiny pin 2)
#define ledPin 1       // Led pin is connected on pin D1 (ATtiny pin 3)
#define tempPower 2    // TMP36 Power pin is connected on pin PB2 - D2 (ATtiny pin 5)

// define user variables
int data_type = 1;          // 1 digit (0-9) data type = 1 for temp sensor
char * serial = "0001";     // 4 hex digit (0-F) sensor serial number
int period = 300;           // # seconds period between trasmissions < 32767 (9,13 hours)

// define software variables
int count = 0;
int tempReading = 0;

void setup(void)
{
// Serial.begin(9600); //DEBUG
// printf_begin(); //DEBUG
 analogReference(INTERNAL);    // set the aref to the internal 1.1V reference
 pinMode(tempPower, OUTPUT);   // set power pin for TMP36 to output
 pinMode(ledPin, OUTPUT);      // set power pin for LED to output
 pinMode(radioPower, OUTPUT);  // set power pin for EN Voltage regulator
 period = period * 0.9;        // 10% Narcoleptic library correction
}

void loop(void)
{
 bitClear(PRR, PRADC); // power up the ADC
 ADCSRA |= bit(ADEN);  // enable the ADC

 digitalWrite(tempPower, HIGH);   // turn TMP36 sensor on
 digitalWrite(ledPin, HIGH);      // turn LED on
 delay(20);                       // Allow 20ms for the sensor to be ready
 analogRead(tempPin);             // first junk read
 for(int i = 0; i < 10 ; i++)     // take 10 more readings
  {
   tempReading += analogRead(tempPin); // accumulate readings
  }
 tempReading = tempReading / 10 ;      // calculate the average
 digitalWrite(ledPin, LOW);            // turn LED off
 digitalWrite(tempPower, LOW);         // turn TMP36 sensor off

 long vcc=readVcc();

 ADCSRA &= ~ bit(ADEN); // disable the ADC
 bitSet(PRR, PRADC);    // power down the ADC

 // Calibration
 double voltage = tempReading * 0.942382812;

 // Temp calculation for TMP36
 // double temperatureC = (voltage - 500) / 10;

 // Temp calculation for TMP37
 double temperatureC = voltage / 20;

 // Final temp calculation
 int temptx = temperatureC * 100;

 // Preparing Payload (32 bytes is maximum)
 char outBuffer[32]= "";
 sprintf(outBuffer,"%d:%s:%04d:%04d:%04d:",data_type,serial,count,temptx,vcc);
 // Serial.println(outBuffer); //DEBUG

 // turn Voltage Regulator ON
 digitalWrite(radioPower, HIGH);
 delay(5);

 // init radio for writing on channel 76
 radio.begin();
 radio.setPALevel(RF24_PA_MAX);
 radio.setChannel(0x4c);
 radio.setDataRate(RF24_250KBPS);
 radio.openWritingPipe(0xF0F0F0F0E1LL);
 radio.enableDynamicPayloads();
 radio.powerUp();
 // radio.printDetails(); //DEBUG

 // Transmit and go down.
 delay(5);
 radio.write(outBuffer, strlen(outBuffer));
 radio.powerDown();
 digitalWrite(radioPower, LOW); // turn Voltage Regulator OFF

 // pause between trasmissions
 int timerSeconds = period % 30;
 int timerHalfminutes = ((period - timerSeconds)/30);

 if (timerHalfminutes > 0)
  {
    for(int i = 0; i < timerHalfminutes ; i++)
     {
      Narcoleptic.delay(30000);   // 30 sec. sleeping is max for Narcoleptic
     }
  }
  if (timerSeconds > 0)
  {
  Narcoleptic.delay(timerSeconds*1000);  // delay the rest
  }

 // increase counter
 count ++;
 if (count == 10000) {
   count = 1;
   }
}

 long readVcc() {
   long result;
   // Read 1.1V reference against Vcc
   ADMUX = _BV(MUX5) | _BV(MUX0);
   delay(2); // Wait for Vref to settle
   ADCSRA |= _BV(ADSC); // Convert
   while (bit_is_set(ADCSRA,ADSC));
   result = ADCL;
   result |= ADCH<<8;
   result = 1126400L / result; // Back-calculate Vcc in mV
   return result;
}
