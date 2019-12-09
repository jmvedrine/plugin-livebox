Présentation
===
Ce plugin permet de récupérer des informations de la Livebox et de lancer des actions.
Il a été développé à l'origine par Thomas Guenneguez.

Compatibilité :
---

Le plugin a été testé avec les versions suivantes de la Livebox :
- Livebox 2
- Livebox Play
- Livebox 4 (depuis la version du 09/12/2019)

Informations visibles :
---

- **Etat connexion** : état de la connexion internet (xDSL ou Fibre)
- **Etat synchro** : état de la synchronisation xDSL ou FTTH
- **Etat TV** : état du service TV
- **Etat VoIP** : état du service téléphonie par internet
- **Débits descendant et montant** : débits de la liaison xDSL upload et download (pas compatible Fibre)
- **IP Wan** : adresse IP publique (IPv4)
- **IPv6 Wan** : adresse IP publique (IPv6)
- **Numéro de téléphone** : numéro de téléphone VoIP
- **Etat Wifi** : état du service Wifi. Pour Livebox 2 : uniquement Wifi 2.4Ghz. Pour les Livebox Play et 4 : état du service Wifi pour les fréquences 2.4Ghz et 5Ghz
- **Etat Wifi invité** : état du service Wifi invité pour la Livebox 4

Actions possibles :
---

- **Activation/Désactivation du WiFi** : permet d'activer ou de désactiver le WiFi. Pour Livebox 2 : uniquement le Wifi 2.4Ghz. Pour Livebox Play et 4 : activation/désactivation par carte WiFi (2.4Ghz et/ou 5Ghz).
- **Activation/Désactivation du WiFi invité** : Pour Livebox 4, permet d'activer ou de désactiver le WiFi invité (les fréquences activées sont les mêmes que pour le Wifi privé).
- **Reboot** : permet de redémarrer la Livebox
- **Sonner** : permet de faire sonner votre téléphone VoIP pendant 5 secondes (pour tester le fonctionnement entre la Livebox et votre téléphone)

Historiser activable pour :
---

- **Etat connexion** : état de la connexion internet (xDSL ou Fibre)
- **Etat synchro** : état de la synchronisation xDSL ou FTTH
- **Etat TV** : état du service TV
- **Etat VoIP** : état du service téléphonie par internet
- **Etat Wifi** : état du service Wifi. Pour Livebox 2 : uniquement Wifi 2.4Ghz. Pour Livebox Play : état du service Wifi pour les fréquences 2.4Ghz et 5Ghz

![informations01](../images/livebox1.png)
![informations02](../images/livebox_screenshot2.png)

Installation/Configuration
===

Nous allons maintenant paramétrer l'équipement. Pour se faire, cliquer sur **Plugins / Communicaton / Livebox**

Puis définir :

- Objet parent
- Catégorie (optionnelle)
- Activer (coché par défaut)
- Visible (optionnel si vous ne désirez pas le rendre visible sur le Dashboard)
- Mot de passe de la Livebox (à modifier s'il ne s'agit pas de celui par défaut)

Et pour finir, cliquer sur Sauvegarder

Les paramètres accessibles sont les suivants :
- Etat
- Etat synchro
- Etat connexion
- Etat TV
- IP Wan
- IPv6 Wan
- Dernier refresh
- Etat Wifi ou Etat wifi 2.4G et Etat wifi 5G
- Liste des équipements

Les commandes sont les suivantes :
- Reboot
- Sonner
- WPS Push Button
- Activer wifi ou Activer wifi 2.4G et Activer wifi 5G
- Désactiver wifi ou Désactiver wifi 2.4G et Désactiver wifi 5G

Les paramètres suivants ne sont visibles que si le protocole VOIP était activé lors de la sauvegarde de l'équipement :
- Etat VoIP <protocole>
- Numéro de téléphone <protocole>
- nombre et tableau des appels entrants, sortants et manqués. Nombre total des appels.

Les paramètres suivants ne sont visibles qu'en connexion de type dsl ou vdsl :
- Débit montant
- Débit descendant
- Marge de bruit montant
- Marge de bruit descendant

FAQ
===

*Pourquoi le plugin est gratuit ?*

Ce plugin est gratuit pour que chacun puisse en profiter simplement.

*J'aimerais remonter des erreurs/modifications directement dans le code ?*

C'est tout à fait possible via le forum communautaire Jeedom https://community.jeedom.com/ catégorie Plugins -> Communication. Créez un nouveau sujet et n'oubliez pas de lui ajouter le tag "plugin-livebox".

*Quelle est la fréquence de rafraîchissement des informations ?*

Le plugin actualise les informations toutes les minutes (modifiable dans le "Moteur de tâches")

*Est-il possible d'historiser les débits ?*

Pour historiser, il faut aller dans le menu Plugin => Communication => Livebox et activer l'historique sur les indicateurs comme pour n'importe quel indicateur.

*Avec quelles Livebox est-ce compatible ?*

- Livebox 2
- Livebox Play
- Livebox 4

Faute de matériel le plugin n'a pas été testé avec la Livebox 5. Merci de remonter les infos sur le forum si vous essayez le plugin avec une livebox 5.

*Est-ce compatible avec la fibre ?*

Oui

*Je n'ai pas d'information sur le débit, est-ce normal ?*

L'API de la livebox en mode FTTH ne fournit pas le débit. Nous ne pouvons donc pas le récupérer.

*Le mot de passe que je saisis ne fonctionne pas.*

Le plugin ne suporte pas certains caractères spéciaux genre # et @.

*Je change de Livebox, faut-il faire quelque chose ?*

Il est nécessaire de resauvegarder l'équipement Livebox pour que le plugin gère bien la détection du modèle.
