# ACQ-DEV-005 — Sécurité et Authentification
**Massagrafik — Juin 2026**

---

## 1. Décisions figées

- **D1** : Custom Authenticator (AppAuthenticator) — pas form_login en V1.
- **D2** : Voters Symfony pour toutes les autorisations d'accès aux entités.
- **D3** : CSRF token obligatoire sur tous les formulaires de mutation.
- **D4** : OwnershipChecker applicatif pour la sécurité métier (propriété des entités).
- **D5** : login_throttling activé dès V1.
- **D6** : Voters testables indépendamment (PHPUnit unitaire).
- **D7** : Voter = décision binaire GRANT/DENY uniquement. Pas de logique métier dans les voters.

---

## 2. security.yaml

```yaml
security:
    password_hashers:
        App\Domain\Shared\Entity\User:
            algorithm: bcrypt
            cost: 12

    providers:
        app_user_provider:
            entity:
                class: App\Domain\Shared\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Http\Security\AppAuthenticator
            logout:
                path: app_logout
                target: app_login
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800
            login_throttling:
                max_attempts: 5
                interval: '1 minute'

    access_control:
        - { path: ^/connexion, roles: PUBLIC_ACCESS }
        - { path: ^/inscription, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }
```

---

## 3. AppAuthenticator

```php
// App\Http\Security\AppAuthenticator
// implements AuthenticatorInterface (pas AbstractLoginFormAuthenticator pour le contrôle complet)

public function authenticate(Request $request): PassportInterface
{
    $email    = $request->request->get('email', '');
    $password = $request->request->get('password', '');

    return new Passport(
        new UserBadge($email),
        new PasswordCredentials($password),
        [
            new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            new RememberMeBadge(),
        ]
    );
}

public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
        return new RedirectResponse($targetPath);
    }
    return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
}
```

---

## 4. Voters — 7 entités protégées

| Voter | Entité | Attributs | Critère |
|---|---|---|---|
| ParcoursVoter | Parcours | VIEW, EDIT, DELETE | parcours.user === user |
| RessourceVoter | Ressource | VIEW, EDIT, DELETE | ressource.parcours.user === user |
| SessionConsolidationVoter | SessionConsolidation | VIEW, SUBMIT | session.ressource.parcours.user === user |
| TraceApprentissageVoter | TraceApprentissage | VIEW, EDIT | trace.user === user |
| ProjetFilRougeVoter | ProjetFilRouge | VIEW, EDIT | projet.parcours.user === user |
| MicroEtapeVoter | MicroEtape | VIEW, COMPLETE, GUIDAGE | etape.projet.parcours.user === user |
| RevisionSpaceeVoter | RevisionSpacee | VIEW, COMPLETE | revision.user === user |

### Exemple — ParcoursVoter

```php
protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
{
    $user = $token->getUser();
    if (!$user instanceof User) {
        return false;
    }

    if (!$subject instanceof Parcours) {
        return false;
    }

    return match ($attribute) {
        'VIEW', 'EDIT', 'DELETE' => $subject->getUser() === $user,
        default => false,
    };
}
```

---

## 5. OwnershipChecker

```php
// App\Application\Security\OwnershipChecker
// Vérifie la propriété des entités dans les Services, avant tout appel AI

public function assertOwns(User $user, object $entity): void
{
    $owner = match (true) {
        $entity instanceof Parcours => $entity->getUser(),
        $entity instanceof Ressource => $entity->getParcours()->getUser(),
        $entity instanceof TraceApprentissage => $entity->getUser(),
        $entity instanceof SessionConsolidation => $entity->getRessource()->getParcours()->getUser(),
        default => throw new \LogicException('Entity type not supported by OwnershipChecker'),
    };

    if ($owner !== $user) {
        throw new AccessDeniedException('Access denied: you do not own this resource.');
    }
}
```

---

## 6. Formulaire d'inscription

```php
// App\Http\Form\RegistrationFormType
// Champs : email, plainPassword (mapped: false), prenom, nom

// Dans RegistrationController :
$user = new User();
$user->setEmail($form['email']->getData());
$user->setPrenom($form['prenom']->getData());
$user->setNom($form['nom']->getData());
$user->setNiveauMaitrise(NiveauMaitrise::DEBUTANT);
$user->setModeAccompagnement(ModeAccompagnement::SOCRATIQUE);
$user->setLangue('fr');
$user->setPassword($passwordHasher->hashPassword($user, $form['plainPassword']->getData()));
```

---

## 7. CSRF — Règles par contexte

| Contexte | Mécanisme | Token name |
|---|---|---|
| Login | `CsrfTokenBadge` dans Passport | `authenticate` |
| Formulaires Symfony Forms | `{{ form_row(form._token) }}` automatique | Auto (form name) |
| Formulaires HTML manuels | `{{ csrf_token('nom_operation') }}` | Libre (à documenter) |
| AJAX | Header `X-CSRF-TOKEN` (V2) | — |

Noms utilisés dans consolidation :
- Questions : `consolidation-{{ session.id }}`
- Exercice : `consolidation-{{ session.id }}`

---

## 8. HTTPS et headers

En prod (Coolify/Nginx) :
```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

---

## Ajustements V1 — Implémentation réelle

### Custom Authenticator — pas form_login

La spec d'origine prévoyait potentiellement `form_login`. L'implémentation utilise `AppAuthenticator` custom, ce qui est **supérieur** : contrôle total des badges, logique de redirection, plus extensible.

### Chemins de routes : `/connexion` et `/inscription`

```php
#[Route('/connexion', name: 'app_login')]
#[Route('/inscription', name: 'app_register')]
```
(pas `/login`, `/register` comme dans une app Symfony générique)

### RegistrationFormType — `data_class: null`

```php
// Spec : potentiellement bindé sur User
// Implémenté :
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults(['data_class' => null]);
}
```
Avantage : évite l'exposition des champs internes de User via mass-assignment. Construction manuelle dans le Controller.

### User à l'inscription — toujours DEBUTANT

Décision produit V1 : pas de choix de niveau à l'inscription. Le niveau `DEBUTANT` est assigné programmatiquement dans le Controller — pas exposé via le formulaire.

### Voters — chaîne de propriété

`SessionConsolidationVoter` traverse 3 niveaux (session → ressource → parcours → user). Acceptable en V1 avec Doctrine lazy loading. Si performances, ajouter un index ou une méthode `getOwner()` sur les entités.
