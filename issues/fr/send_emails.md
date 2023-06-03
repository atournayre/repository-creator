# Envoyer des emails

## Environnement de développement
- [ ] Mettre en place un service SMTP pour l'environnement de développement (Mailtrap ...)
- [ ] Configurer le service SMTP dans le fichier `.env` du projet
- [ ] Configurer l'email et le nom de l'expéditeur dans le fichier `.env` du projet

## Environnement de recette
- [ ] Mettre en place un service SMTP pour l'environnement de recette (Mailtrap ...) accessible par le client
- [ ] Configurer le service SMTP dans le fichier `.env.recette` du projet en n'oubliant d'encoder les identifiants de connexion
- [ ] Configurer l'email et le nom de l'expéditeur dans le fichier `.env.recette` du projet

## Environnement de production
- [ ] Mettre en place un service SMTP pour l'environnement de production (Mailgun, Sendgrid, ...) accessible par le client
- [ ] Configurer le service SMTP dans le fichier `.env.production` du projet en n'oubliant d'encoder les identifiants de connexion
- [ ] Configurer l'email et le nom de l'expéditeur dans le fichier `.env.production` du projet
- [ ] Configurer le service d'envoi d'email (DNS, SPF, DKIM, DMARC, ...) pour le nom de domaine de l'expéditeur
- [ ] Vérifier que l'adresse email de l'expéditeur sera bien autorisée à envoyer des emails pour le nom de domaine de l'expéditeur
