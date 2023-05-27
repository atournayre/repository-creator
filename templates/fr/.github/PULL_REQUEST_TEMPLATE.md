# :bug: It's not a feature, it's a bug!

Supprimer la section si la PR ne fixe pas un problème, sinon ajouter le lien vers le ticket ou l'issue.

# :loudspeaker: Description

Veuillez inclure un résumé des modifications et du problème connexe. Veuillez également inclure la motivation et le contexte pertinents. Répertoriez toutes les dépendances requises pour ce changement.

# :clipboard: Type de changement

Veuillez supprimer les options qui ne sont pas pertinentes.

|  | Detail
| --- | ---
| :heavy_check_mark: | Correction de bogue (changement ininterrompu qui résout un problème)
| :heavy_check_mark: | Nouvelle fonctionnalité (changement ininterrompu qui ajoute des fonctionnalités)
| :heavy_check_mark: | Modification avec rupture (correction ou fonctionnalité qui empêcherait la fonctionnalité existante de fonctionner comme prévu)
| :heavy_check_mark: | Modification sans rupture (correction ou fonctionnalité qui n'empêcherait pas la fonctionnalité existante de fonctionner comme prévu)
| :heavy_check_mark: | Amélioration
| :heavy_check_mark: | Refactorisation
| :heavy_check_mark: | Cette modification nécessite une mise à jour de la documentation
| :heavy_check_mark: | Cette modification nécessite une communication auprès de l'équipe

# :pencil: Comment cela a-t-il été testé ?

Veuillez décrire les tests que vous avez exécutés pour vérifier vos modifications. Fournissez des instructions afin que nous puissions reproduire. Veuillez également énumérer tous les détails pertinents pour votre configuration de test

:heavy_check_mark:  Essai A
:heavy_check_mark:  Essai B

# :white_check_mark: Liste de contrôle

|  | Detail
| --- | ---
| :heavy_check_mark: | Mon code suit les conventions de ce projet
| :heavy_check_mark: | J'ai effectué une auto-révision de mon code
| :heavy_check_mark: | J'ai commenté mon code, notamment dans les zones difficiles à comprendre
| :heavy_check_mark: | J'ai apporté les modifications correspondantes à la documentation
| :heavy_check_mark: | Mes modifications ne génèrent aucun nouvel avertissement
| :heavy_check_mark: | J'ai ajouté des tests qui prouvent que mon correctif est efficace ou que ma fonctionnalité fonctionne
| :heavy_check_mark: | Les tests unitaires nouveaux et existants passent localement avec mes modifications
| :heavy_check_mark: | Toutes les modifications dépendantes ont été fusionnées et publiées dans les modules en aval
| :heavy_check_mark: | La QA locale passe et aucune nouvelle anomalie n'a été introduite
| :heavy_check_mark: | lint:container passe
| :heavy_check_mark: | lint:yaml config passe

# :question: Comment appliquer le changement ?

|  | Action | Commande
| --- | --- | ---
| :heavy_check_mark: | Build l'environnement | `make build`
| :heavy_check_mark: | Configurer l'environnement | `composer dump-env dev`
| :heavy_check_mark: | Supprimer les tables et les séquences |
| :heavy_check_mark: | Redémarrer Docker | `make restart`
| :heavy_check_mark: | Recharger les fixtures | `make fixtures`

# :information_source: Informations additionnelles

