// nRF24l01_receive.cpp
// nRF24L01+ su RaspberryPi
// code for data RX and store on mysql DB
// compile with: g++ -Ofast -mfpu=vfp -mfloat-abi=hard -march=armv6zk -mtune=arm1176jzf-s -Wall -I../ -lrf24-bcm nRF24l01_receive.cpp -o nRF24l01_receive `mysql_config --cflags` `mysql_config --libs`
// see http://slartitorto.blogspot.it/2015/02/basic-remote-sensor-su-mysql-bars.html for details and DB preparation
// connect:
// nrf24L01:     1   2   3   4   5   6   7
// RaspberryPi:  6   1   22  24  23  19  21

#include <cstdlib>
#include <iostream>
#include <RF24/RF24.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <mysql/mysql.h>
#include <time.h>

#define DATABASE_HOST "remote host"
#define DATABASE_NAME  "db name"
#define DATABASE_USERNAME "db username"
#define DATABASE_PASSWORD "db password"

MYSQL mysql_conn;
using namespace std;

RF24 radio(RPI_V2_GPIO_P1_22, RPI_V2_GPIO_P1_24, BCM2835_SPI_SPEED_8MHZ);


void setup(void)
{
// init radio for reading
    radio.begin();
    radio.enableDynamicPayloads();
    radio.setAutoAck(1);
    radio.setRetries(15,15);
    radio.setDataRate(RF24_250KBPS);
    radio.setPALevel(RF24_PA_MAX);
    radio.setChannel(76);
    radio.setCRCLength(RF24_CRC_16);
    radio.openReadingPipe(1,0xF0F0F0F0E1LL);
    radio.startListening();

//initialize MYSQL object for connections
mysql_init(&mysql_conn);
my_bool reconnect = 1;
mysql_options(&mysql_conn,MYSQL_OPT_RECONNECT,&reconnect);
     //Connect to the database
     if(mysql_real_connect(&mysql_conn, DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME, 0, NULL, 0) != NULL)
     {
         printf("Database connection successful.\r\n");
     }
	else
     { // se non funziona:
     	fprintf(stderr, "%s\n", mysql_error(&mysql_conn));
	int m = 0;
	for (m=0; m<15; m++)
	{
	fprintf(stderr, "Retriyng ... %d\n", m);
	sleep(15);
	if(mysql_real_connect(&mysql_conn, DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME, 0, NULL, 0) == NULL)
     		{
			if(m == 10)
			{
				printf("Database connection error.\r\n");
				printf("Too many retries.\r\n");
				exit(0);
			}
        	}
		else
		{
			printf("Database connection successful.\r\n");
                        break;
                }
        }
     }
} // end setup

void loop(void)
{
    // 32 byte character array is max payload
    char receivePayload[32]="";
	// sleep 20 ms
	usleep(20000);

    while (radio.available())
    {
        // read from radio until payload size is reached
        uint8_t len = radio.getDynamicPayloadSize();
        radio.read(receivePayload, len);

// logging received data

time_t current_time;
char* c_time_string;

/* Obtain current time as seconds elapsed since the Epoch. */
current_time = time(NULL);

/* Convert to local time format. */
c_time_string = ctime(&current_time);

printf("%s", c_time_string);
cout << receivePayload << endl;

// conto i separatori ":"
const char * z = receivePayload;
int separator_count;
int m;
separator_count = 0;
for (m=0; z[m]; m++) {
	if(z[m] == ':') {
		separator_count ++;
		}
	}

// se sono 5, ok
if (separator_count == 5) {

        int data_type = 0;
        char * serial;
        int counter = 0;
        float data = 0;
        float battery = 0;

        char query[256];

        data_type = atoi(strtok (receivePayload, ":"));
        serial = strtok (NULL, ":");
        counter = atoi(strtok (NULL, ":"));
        data = atof(strtok (NULL, ":")) / 100;
        battery = atof(strtok (NULL, ":")) / 1000;

//        printf("%04d %s %04d %.2f %.3f \n",data_type,serial,counter,data,battery);


        sprintf(query, "INSERT INTO rec_data (data_type,serial,counter,data,battery) VALUES (%04d,'%s',%04d,%.2f,%.3f)", data_type, serial, counter, data, battery);

//   printf("%s \n",query);


        mysql_query(&mysql_conn,query);
	}
    }
}

int main(int argc, char** argv)
{
    setup();
    while(1)
        loop();

    return 0;

}
