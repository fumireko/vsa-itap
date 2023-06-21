# vsa-itap
Este é um sistema de registro de atendimentos desenvolvido em PHP e MySQL para o setor de Vigilância Socioassistencial.
## Requisitos
- PHP versão 7 ou superior
- MySQL versão 5.6 ou superior
- Uma instância do servidor web Apache2
## Instalação

1. Clone o repositório
```
git clone https://github.com/fumireko/vsa-itap.git
cd vsa-itap
```
2. Configure as informações de acesso ao banco de dados no arquivo `config.php`.
```
$servername = ""; 
$username = "";
$password = "";
$dbname = "";
```
3. Acesse o arquivo `setup.php` no seu navegador para configurar o sistema.
- O arquivo `sample.sql` possui dados de exemplo para demonstrar as funcionalidades.
- Se deseja importá-lo, execute a linha de comando abaixo:
```
mysql -u {seu_usuario} -p {seu_banco_de_dados} < config/sample.sql
```
4. Certifique de habilitar o `.htaccess` na sua configuração do Apache (`/etc/apache2/apache2.conf`).
- Os trechos que usamo código na pasta `api/` precisam da regra `AllowOverride` abaixo ativada para funcionar.
- A sua configuração deve estar parecida com essa:
```
<VirtualHost *:80>
    ServerName www.exemplo.com
    DocumentRoot /var/www/exemplo

    <Directory /var/www/exemplo>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
5. Acesse o sistema pelo navegador usando as credenciais padrão `admin` e `password`.