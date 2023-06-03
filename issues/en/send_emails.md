# Send emails

## Development environment
- [ ] Set up an SMTP service for the development environment (Mailtrap ...)
- [ ] Configure the SMTP service in the project `.env` file
- [ ] Configure the sender's email and name in the project `.env` file

## Staging environment
- [ ] Set up an SMTP service for the staging environment (Mailtrap ...) accessible by the client
- [ ] Configure the SMTP service in the project `.env.staging` file, don't forget to encode the connection credentials
- [ ] Configure the sender's email and name in the project `.env.staging` file

## Production environment
- [ ] Set up an SMTP service for the production environment (Mailgun, Sendgrid, ...) accessible by the client
- [ ] Configure the SMTP service in the project `.env.production` file, don't forget to encode the connection credentials
- [ ] Configure the sender's email and name in the project `.env.production` file
- [ ] Configure the email sending service (DNS, SPF, DKIM, DMARC, ...) for the sender's domain name
- [ ] Check that the sender's email address will be allowed to send emails for the sender's domain name
