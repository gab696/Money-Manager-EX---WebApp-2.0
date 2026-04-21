# MMEX Web (v2)

UI modernisée pour la [webapp Money Manager EX](https://github.com/moneymanagerex/web-money-manager-ex).
Saisie **step-by-step** mobile-first, pavé numérique plein écran, bottom-sheets avec recherche, multi-utilisateur avec invitations — **tout en restant drop-in compatible avec le desktop MMEX actuel** (pas besoin de patcher le C++).

> **Périmètre.** Saisie de transactions sur mobile uniquement. Elles sont empilées
> dans une file puis aspirées par le desktop à la prochaine synchro. Pas de solde,
> pas d'historique complet, pas de reporting — tout cela reste côté desktop.

![screenshot placeholder](docs/screenshot.png)

## Fonctionnalités

### Saisie
- **Flux step-by-step** adapté au smartphone :
  - **Dépense / Revenu** (4 étapes) : Montant → Date + Compte → Bénéficiaire/Tiers + Catégorie → Notes + Récap
  - **Transfert** (3 étapes) : Montant → Date + Compte source + Compte destination → Notes + Récap
- **Pavé numérique plein écran** pour le montant (chiffres larges, 00, virgule, ⌫, CE, ↩ pour reprendre la dernière tx)
- **Bottom-sheets** avec recherche instantanée pour Compte / Catégorie / Bénéficiaire — avec section "Fréquents" en tête et bouton « ＋ Créer » inline
- **Récapitulatif** avant enregistrement (montant, date, compte, bénéficiaire, catégorie, notes)
- **Auto-fill** : la catégorie par défaut du bénéficiaire est reprise automatiquement
- **Boutons d'effacement** sur chaque chip (catégorie, bénéficiaire)
- Barre de progression colorée selon le type (rouge/vert/bleu)

### File d'attente
- Transactions groupées par jour, montants colorés, type en badge
- Totaux dépenses / revenus en tête
- Tap → édition, duplication, suppression avant sync
- Statut de dernière aspiration desktop

### Multi-utilisateur (nouveau en v2)
- **Rôles** : premier compte créé = admin, autres = utilisateurs standard
- **Invitations** : l'admin génère des liens uniques (valides 7 jours) à partager ; l'invité choisit son identifiant et mot de passe
- **Marquage auto** : chaque saisie peut être taggée `[Par: <username>]` dans les Notes (toggle admin)
- Chaque utilisateur change son propre mot de passe ; l'admin peut réinitialiser celui des autres

### Préférences et i18n
- Langues : **Français** / **English** (personnalisable par utilisateur)
- Statut par défaut configurable : Non pointé / Rapproché / Suivi / Doublon / Annulé
- Masquage optionnel des champs Bénéficiaire et Catégorie (comme l'ancien webapp)

### Sync desktop
- Protocole `services.php` **identique à l'original** — aucun patch MMEX desktop requis
- Schéma SQLite **identique à l'original** (5 tables, noms de colonnes, types, collations)
- GUID visible dans les réglages, régénérable par l'admin

## Prérequis

- PHP **8.0+** avec extension `pdo_sqlite` (activée par défaut sur la plupart des hébergements)
- Apache avec `mod_rewrite` (ou Nginx — config plus bas)
- Droits d'écriture sur le dossier d'installation (pour créer le fichier SQLite)
- Aucune base MySQL. Aucun Node.js. Aucun Docker obligatoire.

Testé sur hébergements mutualisés Infomaniak, Hostpoint, O2Switch.

## Installation (3 minutes)

1. **Uploader** tous les fichiers à la racine de ton espace web (ou dans un sous-dossier)
2. Vérifier que le dossier est **inscriptible** par PHP (`chmod 755` ou `775`)
3. Ouvrir `https://ton-domaine.ch/` dans le navigateur
   - Au premier lancement, `MMEX_New_Transaction.db` est créé automatiquement
   - Écran de setup : choisis un utilisateur + mot de passe → ce compte sera **admin**
4. Une fois connecté, va dans **Réglages → Synchronisation** pour récupérer l'URL `services.php` et le GUID à coller dans le desktop

## Configuration du desktop MMEX (inchangée)

Comme pour le webapp original :

1. Ouvrir MMEX desktop
2. *Options → Network → WebApp Settings*
3. Coller l'URL (ex. `https://ton-domaine.ch/services.php`) et le GUID
4. *Tools → Refresh WebApp* → le desktop pousse comptes / catégories / bénéficiaires
5. Les transactions saisies depuis mobile seront aspirées à chaque *Refresh WebApp*

Le protocole `services.php` (URLs, paramètres, format JSON, codes de statut) est reproduit à l'identique — le desktop ne voit aucune différence.

## Migration depuis l'ancien webapp

Si tu disposes déjà d'un `MMEX_New_Transaction.db` généré par l'ancien webapp :
1. Pose-le à la racine **avant** le premier lancement
2. La webapp l'utilise tel quel (le schéma est identique)
3. Ton ancien utilisateur (`Parameters.Username` / `Password` sha512) est automatiquement migré vers la nouvelle table `Users` avec le rôle admin
4. À ta prochaine connexion, ton mot de passe est re-hashé en bcrypt de façon transparente

## Inviter des utilisateurs supplémentaires

1. Connecte-toi en admin → **Réglages → Invitations**
2. Clique sur "Générer un lien" → un lien unique de 7 jours apparaît
3. Copie-le et envoie-le (SMS, mail, Signal, peu importe)
4. L'invité ouvre le lien → choisit son propre identifiant/mot de passe → est auto-connecté
5. Tu peux révoquer un lien tant qu'il n'a pas été utilisé

Les invitations utilisées ou expirées depuis plus de 30 jours sont purgées automatiquement.

## Déploiement dans un sous-dossier

Exemple pour `https://ton-domaine.ch/mmex/` :
- Uploader dans `public_html/mmex/`
- Copier `config.php.example` vers `config.php`, régler `'base_url' => '/mmex'`
- Dans `.htaccess`, dé-commenter `RewriteBase /mmex/`

## Sans Apache (Nginx)

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
location ~ \.(db|sqlite|sqlite3)$ { deny all; }
location ~ ^/config\.php$         { deny all; }
location ~ ^/(app|prototype)/     { deny all; }
```

## Arborescence

```
/                              ← racine web
├── index.php                  ← front controller (UI)
├── services.php               ← API sync attendue par le desktop MMEX (protocole original)
├── MMEX_New_Transaction.db    ← SQLite, créé auto au 1er lancement
├── .htaccess                  ← rewrite + protection .db / config.php
├── config.php                 ← optionnel (copier depuis config.php.example)
├── config.php.example
├── README.md
├── assets/
│   ├── style.css              ← compléments Tailwind
│   └── app.js                 ← logique Alpine (wizard, bottom-sheets, keypad, AJAX)
├── attachments/               ← pièces jointes (créé auto, prévu pour usage futur)
├── app/                       ← code applicatif (bloqué par .htaccess)
│   ├── bootstrap.php
│   ├── Auth.php Config.php Csrf.php Db.php Router.php View.php I18n.php
│   ├── Controllers/
│   │   ├── AuthController.php          (login, setup, invitations publiques)
│   │   ├── TransactionController.php   (saisie + update + delete)
│   │   ├── QueueController.php
│   │   ├── ListsController.php         (JSON + création inline payee/compte/catégorie)
│   │   └── SettingsController.php      (prefs, users, invitations, GUID)
│   ├── Models/
│   │   ├── Account.php Category.php Payee.php Transaction.php Attachment.php
│   │   ├── Parameter.php               (table Parameters)
│   │   ├── User.php                    (table Users)
│   │   └── Invitation.php              (table Invitations)
│   ├── Lang/
│   │   ├── fr.php
│   │   └── en.php
│   └── Views/
│       ├── login.php setup.php invite.php new.php queue.php settings.php
│       ├── layout/app.php nav.php
│       └── errors/404.php
└── prototype/                 ← maquettes statiques (optionnel, bloqué en prod)
```

## Schéma SQLite

### Tables attendues par MMEX desktop (identiques à l'original)

| Table | Colonnes clés | Rôle |
|---|---|---|
| `New_Transaction` | ID, Date, Account, ToAccount, Status, Type, Payee, Category, SubCategory, Amount, Notes | File des tx en attente de sync |
| `Account_List` | AccountName (PK) | Comptes poussés par le desktop |
| `Payee_List` | PayeeName (PK), DefCateg, DefSubCateg | Bénéficiaires poussés par le desktop |
| `Category_List` | CategoryName, SubCategoryName (composite PK NOCASE) | Catégories poussées par le desktop |
| `Parameters` | Parameter (PK), Value | Version, DesktopGuid, LastSyncAt, DefaultStatus, DisablePayee, DisableCategory, Language… |

### Tables d'extension MMEX Web (invisibles pour le desktop)

| Table | Colonnes | Rôle |
|---|---|---|
| `Users` | id, username, password_hash (bcrypt), active, is_admin | Comptes utilisateurs |
| `Invitations` | id, token, created_by, expires_at, used_at, used_by | Liens d'invitation |

## Endpoints `services.php` (consommés par le desktop)

Auth : paramètre GET `guid=…` doit matcher `Parameters.DesktopGuid`.

| URL | Action |
|---|---|
| `services.php?check_guid=1&guid=X` | ping |
| `services.php?check_api_version=1&guid=X` | version de l'API (1.0.1) |
| `services.php?delete_bankaccount=1&guid=X` | vide Account_List |
| `services.php?import_bankaccount=1&guid=X` + POST MMEX_Post (JSON) | remplit Account_List |
| `services.php?delete_payee=1&guid=X` / `import_payee=1` | idem pour payees |
| `services.php?delete_category=1&guid=X` / `import_category=1` | idem pour catégories |
| `services.php?download_transaction=1&guid=X` | JSON de toutes les tx en file |
| `services.php?delete_group=1,2,3&guid=X` | supprime après aspiration (acquittement) |
| `services.php?download_attachment=FILE&guid=X` | streame une pièce jointe |
| `services.php?delete_attachment=FILE&guid=X` | supprime une pièce jointe |

Réponse : texte brut `Operation has succeeded` / `Wrong GUID`, ou JSON pour `download_transaction`.

## Routes UI (pour le navigateur)

### Publiques
| Méthode | URL | Rôle |
|---|---|---|
| GET | `/` | redirige vers `/login` |
| GET | `/setup` | premier lancement (pas d'utilisateur) |
| POST | `/setup` | crée le compte admin initial |
| GET | `/login` | page de connexion |
| POST | `/login` | authentification |
| GET | `/invite/{token}` | page d'acceptation d'invitation |
| POST | `/invite/{token}` | création du compte via invitation |

### Authentifié (tout utilisateur)
| Méthode | URL | Rôle |
|---|---|---|
| POST | `/logout` | déconnexion |
| GET | `/new` | formulaire de saisie step-by-step |
| GET | `/transaction/{id}/edit` | édition d'une tx en file |
| POST | `/transaction` | création / mise à jour (JSON) |
| POST | `/transaction/{id}/delete` | suppression |
| GET | `/queue` | file d'attente |
| GET | `/api/lists` | comptes / catégories / payees en JSON |
| POST | `/api/payees` / `/api/accounts` / `/api/categories` | création inline |
| GET | `/settings` | réglages |
| POST | `/settings/password` | changement de son propre mot de passe |
| POST | `/settings/preferences` | préférences (langue pour tous, reste admin) |

### Admin uniquement
| Méthode | URL | Rôle |
|---|---|---|
| POST | `/settings/guid` | régénère le GUID desktop |
| POST | `/settings/users/{id}/delete` | supprime un utilisateur |
| POST | `/settings/users/{id}/password` | réinitialise le mot de passe d'un utilisateur |
| POST | `/settings/invitations` | génère un lien d'invitation |
| POST | `/settings/invitations/{id}/revoke` | révoque une invitation |

## Sécurité

- Mots de passe **bcrypt** (`password_hash` / `password_verify`) pour tous les nouveaux comptes
- Migration transparente des anciens hash sha512 au premier login réussi
- Sessions HttpOnly + SameSite=Lax + Secure auto sous HTTPS
- CSRF sur tous les POST (`Csrf::assertPost()`)
- SQL via PDO préparé (pas de concaténation)
- `app/`, `config.php`, `.db` bloqués par `.htaccess`
- Rôles : `Auth::requireAdmin()` renvoie 403 sur toutes les routes admin
- Invitations : tokens de 48 caractères hex (24 bytes aléatoires), expiration 7 jours, usage unique

## Développement local

```bash
# Depuis la racine du projet :
php -S 127.0.0.1:8080 index.php
# → http://127.0.0.1:8080/
```

Note : le serveur built-in n'applique pas `.htaccess`, mais `index.php` sert à la fois de front controller et de router de fallback (services.php reste accessible directement car c'est un fichier réel).

Activer le debug : copier `config.php.example` vers `config.php` puis mettre `'debug' => true`.

## Champs personnalisés MMEX — limitation connue

Les "custom fields" définis dans MMEX desktop (table `CUSTOMFIELDDATA_V1`) **ne sont pas supportés par le protocole de sync webapp** — cette limitation vient du desktop, pas de cette webapp.

Contournement disponible : activer dans **Réglages → Champs personnalisés** le marquage automatique de l'utilisateur. Chaque saisie se voit préfixée `[Par: <nom>] ` dans le champ Notes standard, qui arrive bien côté desktop. Pour un vrai custom field, il faudrait patcher le C++ du desktop (voir discussion dans l'historique du projet).

## Feuille de route

- [x] Saisie step-by-step dépense / revenu / transfert
- [x] File d'attente + édition / suppression / duplication
- [x] Protocole `services.php` identique à l'original
- [x] Schéma SQLite identique à l'original (+ tables Users / Invitations en extension)
- [x] Multi-utilisateur avec rôle admin + invitations par lien
- [x] i18n FR / EN
- [x] Marquage automatique de l'utilisateur dans Notes (workaround custom fields)
- [ ] Upload de pièces jointes depuis mobile (schéma déjà prêt côté serveur)
- [ ] PWA (manifest + service worker + queue IndexedDB offline)
- [ ] Biométrie via WebAuthn (Face ID / Touch ID / clé hardware)
- [ ] Traductions IT / DE / ES

## Licence

GPL-2.0, comme Money Manager EX.
