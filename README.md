# vsa-itap
Este é um sistema de registro de atendimentos desenvolvido em PHP e MySQL para o setor de Vigilância Socioassistencial.
## Requisitos
- PHP versão 7 ou superior
- MySQL versão 5.6 ou superior
- Servidor Apache instalado
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
3. Execute o arquivo `setup.php` ou importe o arquivo `setup.sql`.
```
mysql -u usuario -p nome_do_banco_de_dados < config/setup.sql
```
4. Certifique de habilitar o .htaccess na sua configuração do Apache (`/etc/apache2/apache2.conf`).
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


