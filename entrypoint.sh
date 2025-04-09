#!/bin/sh

# Aguardar o MySQL estar pronto
echo "Aguardando MySQL..."
while ! nc -z mysql 3306; do
  sleep 1
done
echo "MySQL está pronto."

# Aguardar o RabbitMQ estar pronto
echo "Aguardando RabbitMQ..."
while ! nc -z rabbitmq 5672; do
  sleep 1
done
echo "RabbitMQ está pronto."

# Executar migrações
# php artisan migrate --force

# Iniciar consumidor de eventos RabbitMQ
php -r '
use PhpAmqpLib\Connection\AMQPStreamConnection;

require "vendor/autoload.php";

$connection = new AMQPStreamConnection("rabbitmq", 5672, "guest", "guest");
$channel = $connection->channel();
$channel->queue_declare("transactions", false, true, false, false);

$callback = function ($msg) {
    $data = json_decode($msg->body, true);
    file_put_contents("storage/logs/consumer.log", "[" . date("Y-m-d H:i:s") . "] Evento recebido: " . json_encode($data) . PHP_EOL, FILE_APPEND);
    // Aqui você pode persistir o evento no banco, exemplo:
    App\Models\Transaction::create($data);
};

$channel->basic_consume("transactions", "", false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
'

# Se quiser também servir HTTP no query-service, ative abaixo:
exec php-fpm
