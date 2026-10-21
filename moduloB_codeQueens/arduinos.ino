
#include <DHT.h>
#include <math.h>

#define PIN_DHT       2
#define PIN_PORTA     3
#define PIN_BOTAO     4
#define PIN_BUZZER    5

#define LED_VERDE     6
#define LED_AMARELO   7
#define LED_VERMELHO  8

#define PIN_SOM       A1

#define TIPO_DHT DHT11

DHT dht(PIN_DHT, TIPO_DHT);

const float TEMP_MIN = 18.0;
const float TEMP_MAX = 28.0;


const unsigned long INTERVALO_LEITURA = 5000;
const unsigned long TEMPO_ATENCAO_PORTA = 20000;
const unsigned long TEMPO_CRITICO_PORTA = 60000;
const unsigned long TEMPO_CRITICO_TEMP = 30000;
const unsigned long INTERVALO_BIP = 10000;
const unsigned long DURACAO_BIP = 200;
const unsigned long INTERVALO_PISCA = 500;

enum Estado {
  NORMAL,
  ATENCAO,
  CRITICO,
  RECONHECIDO,
  FALHA_SENSOR
};

Estado estadoAtual = NORMAL;
Estado estadoAnterior = NORMAL;

float temperatura = NAN;
float umidade = NAN;

int nivelSom = 0;
int falhasDHT = 0;

bool portaAberta = false;
bool alarmeReconhecido = false;
bool ledPiscando = false;
bool buzzerLigado = false;

bool botaoAnterior = HIGH;

unsigned long ultimaLeitura = 0;
unsigned long inicioPortaAberta = 0;
unsigned long inicioTemperaturaCritica = 0;
unsigned long ultimoBip = 0;
unsigned long inicioBip = 0;
unsigned long ultimoPisca = 0;


const char* nomeEstado(Estado estado) {
  switch (estado) {
    case NORMAL:       return "NORMAL";
    case ATENCAO:      return "ATENCAO";
    case CRITICO:      return "CRITICO";
    case RECONHECIDO:  return "RECONHECIDO";
    case FALHA_SENSOR: return "FALHA_SENSOR";
    default:           return "DESCONHECIDO";
  }
}

void desligarLEDs() {
  digitalWrite(LED_VERDE, LOW);
  digitalWrite(LED_AMARELO, LOW);
  digitalWrite(LED_VERMELHO, LOW);
}

void lerSensores() {
  temperatura = dht.readTemperature();
  umidade = dht.readHumidity();

  nivelSom = analogRead(PIN_SOM);

  portaAberta = digitalRead(PIN_PORTA) == HIGH;

  if (isnan(temperatura) || isnan(umidade)) {
    falhasDHT++;

    Serial.print("Falha de leitura DHT11: ");
    Serial.println(falhasDHT);
  } else {
    falhasDHT = 0;

    Serial.print("Temperatura: ");
    Serial.print(temperatura);
    Serial.print(" C | Umidade: ");
    Serial.print(umidade);
    Serial.print("% | Som AO: ");
    Serial.print(nivelSom);
    Serial.print(" | Porta: ");
    Serial.println(portaAberta ? "ABERTA" : "FECHADA");
  }

  unsigned long agora = millis();


  if (portaAberta) {
    if (inicioPortaAberta == 0) {
      inicioPortaAberta = agora;
    }
  } else {
    inicioPortaAberta = 0;
  }


  bool temperaturaFora =
    !isnan(temperatura) &&
    (temperatura < TEMP_MIN || temperatura > TEMP_MAX);

  if (temperaturaFora) {
    if (inicioTemperaturaCritica == 0) {
      inicioTemperaturaCritica = agora;
    }
  } else {
    inicioTemperaturaCritica = 0;
  }
}


void verificarBotao() {
  bool botaoAtual = digitalRead(PIN_BOTAO);


  if (botaoAnterior == HIGH && botaoAtual == LOW) {
    if (estadoAtual == CRITICO ||
        estadoAtual == RECONHECIDO) {
      alarmeReconhecido = true;
      Serial.println("Alarme reconhecido pelo usuario.");
    }
  }

  botaoAnterior = botaoAtual;
}


void atualizarEstado() {
  unsigned long agora = millis();


  if (falhasDHT >= 3) {
    estadoAtual = FALHA_SENSOR;
    alarmeReconhecido = false;
    return;
  }

  bool temperaturaCritica =
    inicioTemperaturaCritica != 0 &&
    agora - inicioTemperaturaCritica >=
    TEMPO_CRITICO_TEMP;

  bool portaCritica =
    inicioPortaAberta != 0 &&
    agora - inicioPortaAberta >=
    TEMPO_CRITICO_PORTA;

  bool temperaturaProxima =
    !isnan(temperatura) &&
    (temperatura <= TEMP_MIN + 1 ||
     temperatura >= TEMP_MAX - 1);

  bool portaEmAtencao =
    inicioPortaAberta != 0 &&
    agora - inicioPortaAberta >=
    TEMPO_ATENCAO_PORTA;

  bool condicaoCritica =
    temperaturaCritica || portaCritica;


  if (!condicaoCritica) {
    alarmeReconhecido = false;
  }

  if (condicaoCritica) {
    if (alarmeReconhecido) {
      estadoAtual = RECONHECIDO;
    } else {
      estadoAtual = CRITICO;
    }
  } else if (temperaturaProxima || portaEmAtencao) {
    estadoAtual = ATENCAO;
  } else {
    estadoAtual = NORMAL;
  }
}


void controlarSaidas() {
  unsigned long agora = millis();

  desligarLEDs();

  switch (estadoAtual) {

    case NORMAL:
      digitalWrite(LED_VERDE, HIGH);
      digitalWrite(PIN_BUZZER, LOW);
      buzzerLigado = false;
      break;

    case ATENCAO:
      digitalWrite(LED_AMARELO, HIGH);


      if (!buzzerLigado &&
          agora - ultimoBip >= INTERVALO_BIP) {
        ultimoBip = agora;
        inicioBip = agora;
        buzzerLigado = true;
        digitalWrite(PIN_BUZZER, HIGH);
      }

      if (buzzerLigado &&
          agora - inicioBip >= DURACAO_BIP) {
        buzzerLigado = false;
        digitalWrite(PIN_BUZZER, LOW);
      }
      break;

    case CRITICO:
      digitalWrite(LED_VERMELHO, HIGH);
      digitalWrite(PIN_BUZZER, HIGH);
      buzzerLigado = true;
      break;

    case RECONHECIDO:
      if (agora - ultimoPisca >= INTERVALO_PISCA) {
        ultimoPisca = agora;
        ledPiscando = !ledPiscando;
      }

      digitalWrite(LED_VERMELHO, ledPiscando);
      digitalWrite(PIN_BUZZER, LOW);
      buzzerLigado = false;
      break;

    case FALHA_SENSOR:
      if (agora - ultimoPisca >= INTERVALO_PISCA) {
        ultimoPisca = agora;
        ledPiscando = !ledPiscando;
      }

      digitalWrite(LED_AMARELO, ledPiscando);
      digitalWrite(LED_VERMELHO, !ledPiscando);
      digitalWrite(PIN_BUZZER, LOW);
      buzzerLigado = false;
      break;
  }
}


void registrarMudancaEstado() {
  if (estadoAtual != estadoAnterior) {
    Serial.print("ESTADO: ");
    Serial.println(nomeEstado(estadoAtual));

    estadoAnterior = estadoAtual;
  }
}


void setup() {
  Serial.begin(9600);

  pinMode(PIN_PORTA, INPUT_PULLUP);
  pinMode(PIN_BOTAO, INPUT_PULLUP);
  pinMode(PIN_BUZZER, OUTPUT);

  pinMode(LED_VERDE, OUTPUT);
  pinMode(LED_AMARELO, OUTPUT);
  pinMode(LED_VERMELHO, OUTPUT);

  dht.begin();

  desligarLEDs();
  digitalWrite(PIN_BUZZER, LOW);


  ultimaLeitura = millis() - INTERVALO_LEITURA;

  Serial.println("================================");
  Serial.println("VaxGuard Connect iniciado!");
  Serial.println("Arduino UNO R4 WiFi");
  Serial.println("Leitura dos sensores: 5 segundos");
  Serial.println("================================");
}

void loop() {
  unsigned long agora = millis();

  verificarBotao();

  if (agora - ultimaLeitura >= INTERVALO_LEITURA) {
    ultimaLeitura = agora;
    lerSensores();
  }

  atualizarEstado();
  registrarMudancaEstado();
  controlarSaidas();
}
