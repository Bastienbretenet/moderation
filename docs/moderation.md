# Règles de modération

Le modérateur ne juge que l'illicéité d'un commentaire au regard du droit français : ni le goût, ni la qualité, ni l'opinion. Les règles ci-dessous sont celles du prompt [`config/moderation/OpenRouter/gpt-4o-mini.md`](../config/moderation/OpenRouter/gpt-4o-mini.md). Ce n'est pas une analyse juridique exhaustive, mais l'identification des grandes catégories de contenus illicites en ligne.

## Catégories

Classées de la plus grave à la moins grave. Quand un commentaire relève de plusieurs catégories, la plus grave l'emporte : une injure raciste est classée `hate_speech` et non `insult` (loi de 1881, art. 33 al. 3).

| Code | Infraction | Texte |
|---|---|---|
| `child_sexual_content` | Représentation ou incitation pédopornographique | Code pénal, art. 227-23 |
| `terrorism_apology` | Provocation directe au terrorisme ou apologie | Code pénal, art. 421-2-5 |
| `crime_against_humanity_denial` | Apologie de crimes contre l'humanité ou de guerre, contestation de crimes contre l'humanité | Loi du 29 juillet 1881, art. 24 al. 5 et 24 bis |
| `threat` | Menace d'atteinte aux personnes ou aux biens | Code pénal, art. 222-17 et suivants |
| `hate_speech` | Provocation à la discrimination, à la haine ou à la violence, injure à caractère discriminatoire | Loi de 1881, art. 24 et 33 al. 3 |
| `harassment` | Harcèlement en ligne, y compris provocation au suicide | Code pénal, art. 222-33-2-2 et 223-13 |
| `privacy_violation` | Divulgation d'informations personnelles exposant une personne à un risque | Code civil, art. 9 ; Code pénal, art. 223-1-1 |
| `defamation` | Imputation d'un fait précis portant atteinte à l'honneur d'une personne identifiable | Loi de 1881, art. 29 al. 1 |
| `insult` | Expression outrageante visant une personne identifiable, sans fait précis | Loi de 1881, art. 29 al. 2 |
| `other` | Contenu manifestement illicite hors des catégories précédentes (filet de sécurité) | — |

Les codes sont stockés en VARCHAR : ajouter une catégorie ne demande qu'une modification de l'enum `IllegalContentCategory`, sans migration. Le spam et la publicité ne sont pas illicites et relèveraient d'une règle éditoriale distincte.

## Hypothèses et garde-fous

- **Seul le manifestement illicite est rejeté.** Critique, même virulente, opinion impopulaire, ironie, satire et vulgarité non dirigée sont publiées. En cas de doute, le commentaire est publié : un modérateur qui rejette trop est aussi un défaut pour un média.
- **Diffamation traitée avec prudence.** Elle suppose un fait précis imputé à une personne identifiable, et le modèle ne peut pas vérifier l'exception de vérité. Un jugement de valeur sur un élu ou une entreprise n'en est pas une ; un rejet abusif sur ce motif s'apparente à de la censure du débat public.
- **Injection de prompt.** Le commentaire est transmis encodé en JSON (`{"comment": "…"}`) et le prompt le déclare comme une donnée : les consignes qu'il contient ne sont pas suivies.
- **Sortie contrôlée.** La réponse est contrainte par un JSON schema strict (verdict, catégorie, explication) puis revalidée par `ModerationResponseParser` : verdict inconnu, catégorie hors enum, rejet sans catégorie ou publication avec catégorie sont refusés.

## Sources

- Loi du 29 juillet 1881 sur la liberté de la presse, art. 24, 24 bis, 29, 33 ([Légifrance](https://www.legifrance.gouv.fr/loda/id/JORFTEXT000000877119)).
- Code pénal, art. 222-17, 222-33-2-2, 223-1-1, 223-13, 227-23, 421-2-5 ([Légifrance](https://www.legifrance.gouv.fr/codes/texte_lc/LEGITEXT000006070719)).
- Code civil, art. 9 ([Légifrance](https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006419288)).
- Loi pour la confiance dans l'économie numérique (LCEN) du 21 juin 2004, art. 6, et règlement européen sur les services numériques (DSA, 2022/2065) pour le cadre des obligations des hébergeurs face aux contenus illicites.
