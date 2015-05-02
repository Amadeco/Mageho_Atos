Mageho_Atos
===========

Method Payment SIPS ATOS

Compatible avec les versions de Magento suivantes : 1.5, 1.6, 1.6.1, 1.6.2.0, 1.7, 1.8, 1.8.1, 1.9

Le module prend en charge la communication avec les banques suivantes (liste non exhaustive) :

    BNP Paribas : solution Mercanet
    HSBC CCF : solution Elysnet
    Société Générale : solution Sogenactif
    Crédit Lyonnais / LCL : solution Sherlock's
    Crédit du Nord / Kolb : solution Webaffaires
    Banque Postale : solution Scelliusnet
    Natixis
    Crédit mutuel de Bretagne : solution Citelis
    Credit Agricole : solution E-transactions.

===========
Nouvelle version 1.1.0.0

Amélioration du retour bancaire :
Enregistrement de la commande, du paiement ainsi que de la transaction.
Envoi du mail de nouvelle commande ainsi que de la facture lorsque le
paiement est accepté.

Suppression de la configuration du module des status de commande
rendant l’installation du module plus compliquée.

Nouveau fichier Abstract afin de créer des méthodes de paiement plus
rapidement. Simplification des méthodes existantes avec la notion
d’héritage de la classe Mageho_Atos_Model_Method_Abstract

On se calque sur les modes de capture de paiement (gateway externe)
Magento pour les modes AUTHOR_CAPTURE & VALIDATION

Nouvelle option dans la page atos/failure (suite à une redirection
d’erreur de paiement ou quand le client annule la transaction) : il lui
suffit de cliquer sur le bouton « régler ma commande » afin qu’il
puisse retourner directement sur le serveur de paiement sans
manipulation supplémentaire.
Dans le but de réduire les abandons de panier et faciliter la prise de
commande. A confirmer.

To do list :
Revoir le paiement en plusieurs fois.
Enregistrer toutes les transactions afin d’avoir une meilleure
visibilité des échéances et afin de pouvoir mieux les retrouver dans le
back-office de votre banque.
Possibilité de choisir le nombre d’échéance pour le paiement en
plusieurs fois.

Ce module a été testé sur un site de production (pour le mode standard)
sur un magento version 1.7.0.0

Toutes vos remarques sont les bienvenues afin d’améliorer davantage ce
module de paiement, qui est une pierre angulaire de nos sites
e-commerce.

Merci.

=========

Pré-requis techniques :

Un contrat monétaire est obligatoire avant l'installation de ce module.
Mettre les banques en concurrence vous permet de mieux négocier les commissions de transactions ainsi que les frais d'installation.

Par la suite, la banque vous fournira des fichiers exécutables, exploités par le module Atos/Sips, que vous devrez placer dans le répertoire lib/atos/ qui se trouve à la racine de votre site Magento.
Faire attention à la version de vos binaires :
64 bits pour les systèmes d'exploitations 64 bits
32 bits pour les systèmes d'exploitations 32 bits

Côté serveur, l'utilisation de la fonction shell_exec() doit être autorisé car le module y fait appel. Pensez également à désactiver le safe_mode.
