## Configuration Stripe pour BookSaw

## Prérequis
Cette application utilise Stripe pour les paiements. Voici comment configurer votre environnement de test.

## 1. Obtenir les clés Stripe (GRATUIT)

1. **Créez un compte Stripe** : https://dashboard.stripe.com/register
2. **Activez le mode TEST** (toggle en haut à droite)
3. **Récupérez vos clés** dans `Developers` → `API keys`

## 2. Configuration locale

Créez le fichier `.env.local` à la racine du projet :

```bash
# Stripe Test Keys
STRIPE_PUBLIC_KEY=pk_test_VotreCléPublique
STRIPE_SECRET_KEY=sk_test_VotreCléSecrète

# Base de données (optionnel si différente)
DATABASE_URL="mysql://root:password@127.0.0.1:3306/commerce_db"
```

## 3. Test de paiement

- **URL** : `/panier` → "Payer avec Stripe"
- **Carte de test** : `4242 4242 4242 4242`
- **Date** : N'importe quelle date future (ex: 12/25)
- **CVC** : N'importe quoi (ex: 123)

## 4. Autres cartes de test Stripe

```
# Succès
4242 4242 4242 4242

# Échec - Insufficient funds
4000 0000 0000 9995

# Échec - Card declined
4000 0000 0000 0002
```

## Sécurité

- Le fichier `.env.local` est ignoré par Git
- Ne committez JAMAIS de vraies clés
- En production, utilisez les variables d'environnement du serveur

##Dépannage

Si les paiements ne fonctionnent pas :
1. Vérifiez que vos clés commencent par `pk_test_` et `sk_test_`
2. Confirmez que vous êtes en mode TEST sur Stripe
3. Vérifiez que `.env.local` est bien à la racine du projet 