# Pay2M SDK para PHP

Este é um repositório que possui uma abstração a API da Pay2M, facilitando a criação de PIX Copia e Cola.

## Installation

A forma mais recomendada de instalar este pacote é através do [composer](http://getcomposer.org/download/).

Para instalar, basta executar o comando abaixo

```bash
$ php composer.phar require astrotechlabs/pay2m-sdk
```

ou adicionar esse linha

```
"astrotechlabs/pay2m-sdk": "^1.1"
```

na seção `require` do seu arquivo `composer.json`.

## Como Usar?
### Minimo para utilização

#### Criar uma cobrança
```php
use AstrotechLabs\Pay2MSdk\Pay2MGateway;
use AstrotechLabs\Pay2MSdk\Pay2MGatewayParams;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\PixData;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\GeneratorData;

// Crie uma instância de Pay2MGateway utilizando como parâmetros o seu ClientId e sua ClientSecret que podem ser adquiridos em seu painel PAY2M
$createPixChargeGateway = new Pay2MGateway(new Pay2MGatewayParams($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']));

// Chame o método createCharge() para criar uma cobrança, este método gera o token de autenticação e cria a cobrança na Pay2M
$response = $createPixChargeGateway->createCharge(new PixData(
    generator: new GeneratorData(
        name: 'Dev Teste', // STRING | Deve ser o mesmo nome que está na sua conta Pay2M
        document: '###########' // STRING | Deve ser o mesmo documento da sua conta Pay2M
    ),
    value: 1, // FLOAT | Valor mínimo 1;
));

print_r($response);
```

#### Saída
```php
[
   "gatewayId" => "D77A2489850C4C2F93942BAFEDB419EC697", 
   "copyPasteUrl" => "00020101021226860014br.gov.bcb.pix256.........99", 
   "details" => [
         "reference_code" => "D77A2489850C4C2F93942BAF......", 
         "content" => "00020101021226860014br.gov.bcb.pix2........99" 
      ], 
   "qrCode" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAATEAAAExCAIAAACbBwI/A......v1AwD/IZOQRSYhi0xCFpmELDIJWWQSssgkZJFJyCKTkEUmIYtMQhaZhCwyCVlkErLIJGSRScgik5BFJiHLPzd...............QSssgkZJFJyCKTkEUmIYtMQhaZhCwyCVlkErLIJGSRScgik5BFJiHLv+XfD4wqDsUhAAAAAElFTkSuQmCC" 
]; 
 
```

### Alternativa

#### Criar uma cobrança com autênticação manual
```php
use AstrotechLabs\Pay2MSdk\Pay2MGateway;
use AstrotechLabs\Pay2MSdk\Pay2MGatewayParams;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\PixData;
use AstrotechLabs\Pay2MSdk\CreatePixCharge\Dto\GeneratorData;

// Crie uma instância de Pay2MGateway utilizando como parâmetros o seu ClientId e sua ClientSecret que podem ser adquiridos em seu painel PAY2M
$createPixChargeGateway = new Pay2MGateway(new Pay2MGatewayParams($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET']));

// Gere o token manualmente e armazene-o
$token = $createPixChargeGateway->getAuthToken();

// Chame o método createCharge() para criar uma cobrança e passe o parâmetro token
$response = $createPixChargeGateway->createCharge(new PixData(
    generator: new GeneratorData(
        name: 'Dev Teste', // STRING | Deve ser o mesmo nome que está na sua conta Pay2M
        document: '###########', // STRING | Deve ser o mesmo documento da sua conta Pay2M
        manualToken: true, // BOOL | O parâmetro como true irá cobrar o token e utilizará o mesmo para gerar a cobrança
        token: $token // STRING | Deve ser o token gerado pelo metodo getAuthToken() da classe Pay2MGateway
    ),
    value: 1, // FLOAT | Valor mínimo 1;
));

print_r($response);
```

#### Saída
```php
[
   "gatewayId" => "D77A2489850C4C2F93942BAFEDB419EC697", 
   "copyPasteUrl" => "00020101021226860014br.gov.bcb.pix256.........99", 
   "details" => [
         "reference_code" => "D77A2489850C4C2F93942BAF......", 
         "content" => "00020101021226860014br.gov.bcb.pix2........99" 
      ], 
   "qrCode" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAATEAAAExCAIAAACbBwI/A......v1AwD/IZOQRSYhi0xCFpmELDIJWWQSssgkZJFJyCKTkEUmIYtMQhaZhCwyCVlkErLIJGSRScgik5BFJiHLPzd...............QSssgkZJFJyCKTkEUmIYtMQhaZhCwyCVlkErLIJGSRScgik5BFJiHLv+XfD4wqDsUhAAAAAElFTkSuQmCC" 
]; 
 
```

## Contributing

Pull Request são bem-vindas. Para mudanças importantes, abra primeiro uma issue para discutir o que você gostaria de mudar.

Certifique-se de atualizar os testes conforme apropriado.

## Licence

Este pacote é lançado sob a licença [MIT](https://choosealicense.com/licenses/mit/). Consulte o pacote [LICENSE](./LICENSE) para obter detalhes.
