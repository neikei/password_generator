# Password Generator

Simple PHP based password generator. Supports generating passwords of unlimited length and selectable complexity. Includes a basic strength meter. Requests against the PHP script are done by an AJAX call, which expects JSON in return.

## Testing environment

### Requirements

- Virtualbox >= 5.2.4
- Vagrant >= 2.0.1
- Vagrant Plugins:
  - vagrant-winnfsd # required for nfs shares on Windows
  - vagrant-vbguest # recommended for virtualbox users

### Quickstart

1. git clone https://github.com/acidstout/password_generator.git
2. cd password_generator
3. vagrant up --provider=virtualbox
4. ... wait ...
5. Open the password generator in your browser: http://192.168.56.154/
