
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

# Version du 06/01/2020

- Widgets pour les durées et ascenseurs pour les tableaux (versions pour Jeedom V3 et V4). Widgets contribués par jpty et Nemeraud.
- Lien avec le plugin agenda s'il est installé pour pouvoir plus facilement programmer les commandes action et voir les programmations
- Possibilité d'aller chercher sur Pages Jaunes les noms des appelants (à activer dans la configuration du plugin). Fonctionnalité basée sur l'idée et le code de Jpty.
- Gestion d'une liste de Favoris pour afficher leur nom même s'il n'est pas donné par Pages Jaunes. Fonctionnalité basée sur l'idée et le code de Jpty.
- Possibilité de regrouper les appels par numéros
- Durée minimum d'un appel pour qu'il soit considéré comme manqué

# Version du 29/02/2020

- Ajout des commandes lastmissedcall, lastincomingcall et lastoutgoingcall (merci jpty)
- Ajout d'un lien sur les numéros de tel non favori et différent de Oups pour permettre d'avoir plus d'infos sur cet appelant que je ne connais pas. (merci jpty)

# Version du 27/03/2020

Version du maintenance, le seul changement est le passage en debug du message d'erreur quand le plugin n'arrive pas à récupérer le cookie de la livebox (cf messages sur le forum voir https://community.jeedom.com/t/desactiver-alerte-du-plugin/21067 ).

# Version du 01/11/2020

Le nom des appelants qui était Oups est maintenant configurable dans la page de configuration

# Version du 13/11/2020

Ajout de styles spécifiques à ce plugin pour les listes d'appels. Auparavant les styles de Jeedom utilisés par d'autre plugin étaient redéfinis. Merci à jpty pour cette correction.

# Version du 23/03/2024

Compatibilité avec PHP 8

# Version du 25/03/2024

- Ajout de la configuration de la fréquence d'actualisation (cron) sur l'équipement livebox
- Ajout d'un bouton pour forcer le rafraichissement des informations
- Ajout d'un bouton permettant l'affichage du mot de password de la livebox
- Ajout d'un panneau sur le bureau (à configurer dans le plugin)

# Version du 28/03/2024

- Compatibilité avec Livebox 6 et 7
- Compatibilité avec core jeedom 4.4 (la version minimum exigée du core jeedom passe de 3.3.28 à 4.1.28)

# Version du 01/04/2024

- Les commandes action et info relatives au WiFi (2.4GHz, 5GHz, 6GHz) ont été corrigées et fonctionnent avec la Livebox 6 (pas encore de retour pour la Livebox 7)
- Ajout de commandes d'activation wifi pour l'ensemble des bandes
- Ajout d'un paramètre de configuration pour permettre de personnaliser entierement les widgets (sinon à chaque enregistrement une configuration par défaut est réappliquée)
- Ajout d'une commande softwareVersion (que vous pouvez historiser dans le menu avancé de la commande)
- Corrige le blocage et deblocage d'un device