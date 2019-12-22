
# WARNING:

Detail complet des mises à jour sur https://github.com/jmvedrine/plugin-livebox/commits/master

# Anciennes évolutions :

- Correction lors de la sauvegarde de la Livebox
- Modification pour compatibilité Jeedom V3
- Plus de suivi de version
- Ajout de la commande reboot
- Ajout du délai de changement d'état de la synchro ADSL en seconde
- Mise a jour des indicateurs, même si la valeur ne change pas pour les graphiques.
- Ajout de bouton "WPS Push Button"
- Liste des équipements
- Correction de variables cachées pour Cookies.
- Modification de cron pour plus d'autonomie.
- Ajout des informations de marge montante et descendante.
- Support de plusieurs Livebox.
- Correction par rapport au nouveau core.
- Correction bug de collecte
- Correction pour désinstallation sans Livebox
- Ajout de lien vers les options d'affichage
- Ajout d'un état de la Livebox
- Correction pour la téléphonie en H.323
- Optimisation pour ne pas mettre à jour les données si elle le sont déjà.
- Suppression de commande reset
- Première version bêta
- Correction de problème de compte

# Version du 09/12/2019

- Correction des commandes Wifi pour la Livebox 4
- Ajout des commandes pour le Wifi invité (état, activation, désactivation) ne marchent qu'avec la Livebox 4
- Ajout des caractéristiques de la box dans la page équipement
- Nouvelles commandes pour les appels entrants, sortants et manqués (nombre et tableau des appels)

# Version du 25/12/2019

- Widgets pour les durées et ascenseurs pour les tableaux (versions pour Jeedom V3 et V4). Widgets contribués par jpty et Nemeraud.
- Lien avec le plugin agenda s'il est installé pour pouvoir plus facilement programmer les commandes action et voir les programmations
- Possibilité d'aller chercher sur Pages Jaunes les noms des appelants (à activer dans la configuration du plugin). Fonctionnalité basée sur l'idée et le code de Jpty.
- Gestion d'une liste de Favoris pour afficher leur nom même s'il n'est pas donné par Pages Jaunes. Fonctionnalité basée sur l'idée et le code de Jpty.
- Durée minimum d'un appel pour qu'il soit considéré comme manqué
