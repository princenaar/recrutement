<?php

namespace App\Support;

class CnisPositions
{
    /**
     * @return array<string, array{title: string, description: string}>
     */
    public static function all(): array
    {
        return [
            'technical_operations' => [
                'title' => 'Spécialiste des Opérations Techniques',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée.

Nous recherchons un(e) Spécialiste des Opérations Techniques pour garantir l'efficacité et la fiabilité des systèmes de déploiement et d'assistance de nos solutions de santé numérique. Ce rôle est essentiel pour la gestion quotidienne des systèmes HOS dans des environnements à faibles ressources, en assurant une performance, une continuité et un support optimaux pour les utilisateurs finaux et les partenaires.

## Responsabilités Clés (Key Responsibilities)
### Support de Niveau 1 et 2 (L1 & L2) et Gestion des Incidents :
- Servir de point de contact principal pour la résolution des problèmes techniques et opérationnels des utilisateurs finaux et des établissements de santé.
- Classer, suivre et résoudre les problèmes du système HOS (logiciels et matériel) en utilisant des systèmes de billetterie (ticketing) et des pratiques de gestion des incidents.
- Gérer la communication avec l'utilisateur pendant les pannes ou les maintenances.

### Gestion des Systèmes et Configuration :
- Gérer et maintenir les systèmes HOS back-end et front-end (par exemple, DHIS2, ODK, CommCare), y compris les mises à jour et les sauvegardes.
- Gérer la configuration, le déploiement et la maintenance des outils numériques sur les appareils des établissements de santé et des Agents de Santé Communautaire (ASC).

### Surveillance et Performance :
- Mettre en œuvre et maintenir des systèmes de surveillance pour suivre l'état, l'utilisation et les performances des applications et de l'infrastructure du HOS.
- Surveiller les intégrations de données (API et flux ETL/ELT simples) et garantir la complétude et la ponctualité des données.

### Documentation et Formation :
- Contribuer à la documentation technique, aux bases de connaissances et aux manuels d'utilisation pour les systèmes HOS.
- Former les utilisateurs finaux (personnel des établissements, agents de santé) aux opérations du système et à la résolution de problèmes de base.

### Optimisation des Processus :
- Identifier les goulots d'étranglement opérationnels et recommander des améliorations de processus pour le déploiement et le support.

## Qualifications et Expérience
- Formation : Diplôme universitaire en Informatique, Technologies de l'Information, ou domaine technique pertinent.
- Expérience : +3 années d'expérience dans un rôle de support technique, d'opérations IT, ou d'administration de systèmes, idéalement dans le secteur de la santé numérique.
- Support : Expérience avérée dans la gestion de systèmes de billetterie (par exemple, Jira, Service Now) et la fourniture d'un support technique à distance et sur site.
- Santé Numérique : Familiarité avec les plateformes de santé numérique courantes (par exemple, DHIS2, OpenMRS, CommCare, ODK).
- Systèmes : Compétences de base en administration de systèmes (Linux/Windows Server) et connaissance des concepts de base des bases de données (SQL).

## Compétences Hautement Souhaitées
- Expérience de travail dans des environnements à faibles ressources ou ruraux.
- Compétences de base en scripting (par exemple, Bash, Python) pour l'automatisation des tâches.
- Connaissance des concepts de base des réseaux et du cloud (AWS, Azure).
- Bilinguisme (anglais et français) est un atout, en particulier pour les rôles en Afrique de l'Ouest.

## Attributs Personnels
- Courage : Volonté de s'exprimer, de remettre en question le statu quo et d'accepter de nouveaux défis.
- Humilité : Ouverture à l'apprentissage, à la recherche d'aide en cas de besoin et une concentration sur le service aux autres.
- Aventure : Une passion pour fixer des objectifs ambitieux, s'attaquer à des tâches difficiles et trouver de la joie dans le voyage.
- Initiative : Résolution proactive des problèmes, un sens de l'appropriation et une volonté d'aller au-delà des attentes.
- Résilience : La capacité à rebondir après des revers, à persévérer face aux défis et à en ressortir plus fort.
MARKDOWN,
            ],
            'bi_full_stack_developer' => [
                'title' => 'Développeur BI Full Stack',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée.

Nous recherchons un **Développeur BI Full Stack** pour concevoir, construire et maintenir l'intégralité de la suite d'outils de Business Intelligence (BI) et de rapports pour le HOS. Ce rôle est "Full Stack" car il couvre tous les aspects, de l'extraction de données brutes aux tableaux de bord interactifs finaux. Vous transformerez des données complexes de santé en visualisations claires et exploitables pour les utilisateurs finaux (analystes, dirigeants, et Ministres de la Santé).

## Responsabilités Clés (Key Responsibilities)
### Modélisation et Intégration des Données :
- Concevoir et développer des modèles de données (par exemple, des schémas en étoile/flocon) optimisés pour la BI et la performance des requêtes dans un environnement de données volumineuses.
- Développer des requêtes et des scripts SQL ou Python efficaces pour transformer les données brutes de santé en ensembles de données structurés et analysables.

### Développement Front-End (Visualisation) :
- Concevoir et construire des tableaux de bord et des rapports visuellement attrayants, conviviaux et interactifs à l'aide d'outils BI (par exemple, Power BI, Tableau, Looker, etc.).
- Travailler avec les parties prenantes pour itérer les conceptions, assurant que les rapports répondent aux exigences analytiques spécifiques.

### Développement Back-End et Performance :
- Optimiser les performances des rapports en ajustant les modèles de données, les requêtes DAX/MDX et les connexions aux sources de données.
- Assurer la fiabilité, la sécurité et l'actualisation des données dans la couche BI.

### Assurance Qualité et Documentation :
- Mettre en œuvre des processus de validation et de test des données pour garantir l'exactitude des rapports.
- Maintenir une documentation complète pour les modèles de données, les mesures et les tableaux de bord.

### Collaboration :
- Travailler en étroite collaboration avec les ingénieurs de données pour influencer l'architecture des données et l'ingestion de données pour les besoins BI.

## Qualifications et Expérience
- Formation : Diplôme universitaire en Informatique, Systèmes d'Information, ou domaine connexe.
- Expérience : + 4 années d'expérience professionnelle dans un rôle de Développeur BI, Analyste BI ou rôle similaire.
- Outils BI : Expertise avérée avec au moins un outil de visualisation majeur (Power BI est préférable).
- SQL et Modélisation : Maîtrise de SQL avancé pour l'analyse et la transformation de données (y compris l'écriture de procédures stockées/vues). Solide compréhension des concepts de modélisation de données pour les entrepôts de données (schémas en étoile/flocon).
- DAX/MDX : Expérience dans la création de mesures et de calculs avancés avec DAX (pour Power BI/SSAS Tabulaire) ou MDX.
- Scripting : Compétences en scripting pour l'ETL (par exemple, Python) sont un atout majeur.

## Compétences Hautement Souhaitées (Highly Desired Skills)
- Expérience de travail dans l'informatique de la santé ou avec des données cliniques/sanitaires.
- Expérience avec des environnements Big Data (par exemple, Spark, Databricks).
- Connaissance des plateformes cloud (AWS, Azure, ou GCP) dans le contexte de l'analytique.

## Attributs Personnels
- Courage : Volonté de s'exprimer, de remettre en question le statu quo et d'accepter de nouveaux défis.
- Humilité : Ouverture à l'apprentissage, à la recherche d'aide en cas de besoin et une concentration sur le service aux autres.
- Aventure : Une passion pour fixer des objectifs ambitieux, s'attaquer à des tâches difficiles et trouver de la joie dans le voyage.
- Initiative : Résolution proactive des problèmes, un sens de l'appropriation et une volonté d'aller au-delà des attentes.
- Résilience : La capacité à rebondir après des revers, à persévérer face aux défis et à en ressortir plus fort.
MARKDOWN,
            ],
            'data_analyst' => [
                'title' => 'Analyste de Données',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée.

Nous recherchons un(e) Analyste de Données qui jouera un rôle essentiel dans l'interprétation des données de santé et l'extraction d'informations exploitables (insights) pour le projet HOS. Ce rôle implique de convertir des données brutes en analyses claires, de créer des rapports significatifs et de soutenir les parties prenantes du secteur de la santé avec des preuves pour la prise de décision. Vous comblerez le fossé entre les données et la compréhension opérationnelle.

## Responsabilités Clés (Key Responsibilities)
### Analyse de Données et Interprétation :
- Effectuer des analyses exploratoires de données (EDA) pour identifier les tendances, les anomalies et les relations dans les données cliniques et opérationnelles.
- Utiliser des techniques statistiques pour tester des hypothèses et valider les conclusions tirées des données.

### Rapports et Visualisation :
- Concevoir, développer et maintenir des tableaux de bord, des rapports et des visualisations interactives et perspicaces à l'aide d'outils BI (par exemple, Power BI, Tableau).
- Communiquer efficacement les résultats de l'analyse aux parties prenantes non techniques, notamment les dirigeants et les fonctionnaires du gouvernement.

### Qualité et Préparation des Données :
- Travailler avec les équipes d'ingénierie des données pour garantir l'exactitude, l'exhaustivité et la cohérence des données.
- Nettoyer, transformer et structurer les données brutes pour les rendre prêtes à l'analyse.

### Soutien à la Décision :
- Fournir un soutien analytique aux équipes opérationnelles et de direction pour mesurer l'impact des interventions du HOS et identifier les domaines d'amélioration des systèmes de santé.
- Développer et maintenir des indicateurs clés de performance (KPI) pour le suivi des performances.

## Qualifications et Expérience
- Formation : Diplôme universitaire en Statistiques, Mathématiques, Informatique, Santé Publique ou domaine analytique connexe.
- Expérience : + 3 années et + d'expérience professionnelle dans un rôle d'Analyste de Données.
- SQL : Maîtrise avancée de SQL pour l'extraction et la manipulation de données à partir de bases de données relationnelles.
- Outils BI : Solide expertise avec au moins un outil de visualisation de données majeur (Power BI est préféré) et capacité à créer des modèles de données efficaces.
- Analyse statistique : Bonne compréhension des concepts statistiques de base et de leur application aux données du monde réel.
- Feuilles de calcul : Maîtrise d'Excel et de Google Sheets pour la manipulation et l'analyse de données ad hoc.

## Compétences Hautement Souhaitées (Highly Desired Skills)
- Expérience de travail dans l'informatique de la santé ou avec des données de santé (par exemple, DSE, indicateurs de santé publique).
- Expérience avec des langages de programmation de données tels que Python ou R.
- Connaissance des plateformes cloud (AWS, Azure, ou GCP) dans le contexte de l'analytique.

## Attributs Personnels
- Courage : Volonté de s'exprimer, de remettre en question le statu quo et d'accepter de nouveaux défis.
- Humilité : Ouverture à l'apprentissage, à la recherche d'aide en cas de besoin et une concentration sur le service aux autres.
- Aventure : Une passion pour fixer des objectifs ambitieux, s'attaquer à des tâches difficiles et trouver de la joie dans le voyage.
- Initiative : Résolution proactive des problèmes, un sens de l'appropriation et une volonté d'aller au-delà des attentes.
- Résilience : La capacité à rebondir après des revers, à persévérer face aux défis et à en ressortir plus fort.
MARKDOWN,
            ],
            'hic_operations_engineer' => [
                'title' => 'Ingénieur des Opérations du CIS (HIC Operations Engineer)',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée. Ces données sont gérées dans notre Centre d'Intelligence Sanitaire (CIS) en utilisant une architecture de données de pointe.

Nous recherchons un(e) Ingénieur(e) des Opérations du CIS pour garantir la fiabilité, la performance et l'efficacité opérationnelle de notre plateforme de données HOS. Vous serez responsable de la gestion quotidienne de l'infrastructure de données, en vous concentrant sur la surveillance, l'automatisation, la résolution des problèmes et le maintien d'une performance optimale des pipelines de données et de l'entrepôt de données.

## Responsabilités Clés (Key Responsibilities)
### Surveillance et Observabilité :
- Mettre en œuvre et maintenir des outils de surveillance complets (Prometheus, Grafana, ELK/Loki) pour l'infrastructure de données, les pipelines ETL/ELT, et les bases de données.
- Développer des tableaux de bord d'observabilité clairs pour les indicateurs clés de performance (KPI) des données, de l'infrastructure et des opérations de la plateforme.

### Gestion des Incidents et SRE (Site Reliability Engineering) :
- Répondre aux alertes et résoudre rapidement les incidents liés aux pipelines de données, à la qualité des données, ou aux problèmes d'infrastructure.
- Participer à la rotation d'astreinte (on-call rotation) pour garantir la disponibilité et la fiabilité des systèmes critiques.
- Mener des analyses de causes profondes (Post-Mortems) pour prévenir la récurrence des problèmes.

### Maintenance des Pipelines de Données :
- Surveiller les jobs ETL/ELT (par exemple, Airflow) pour garantir la complétude et l'actualité des données.
- Travailler avec les ingénieurs de données pour optimiser les performances des requêtes SQL et des processus de transformation.

### Automatisation et Outillage :
- Développer des outils et des scripts d'automatisation (principalement en Python ou Bash) pour simplifier les tâches opérationnelles répétitives, y compris la gestion des déploiements et des configurations.
- Maintenir l'Infrastructure as Code (IaC) pour les composants d'infrastructure de données.

### Sécurité et Conformité :
- S'assurer que les systèmes de données sont opérationnels et maintenus en respectant les normes de sécurité (par exemple, gestion des accès, chiffrement).
- Maintenir la conformité aux réglementations de confidentialité des données (telles que HIPAA/GDPR) dans les opérations quotidiennes.

## Qualifications et Expérience
- Formation : Diplôme universitaire en Informatique, Ingénierie, ou domaine technique pertinent.
- Expérience : 3 années et + d'expérience professionnelle dans un rôle SRE, DevOps, ou Opérations de Plateforme de Données.
- Surveillance : Expérience pratique avec les outils de surveillance et d'alerte (par exemple, Prometheus, Grafana).
- Cloud/Linux : Maîtrise de l'administration Linux et expérience des plateformes cloud (AWS, Azure ou GCP).
- Scripting : Solide maîtrise du scripting (idéalement Python et Bash) pour l'automatisation.
- SQL : Bonnes compétences en SQL pour le dépannage des bases de données et des pipelines.
- Conteneurisation : Familiarité avec Docker et Kubernetes (K8s).

## Compétences Hautement Souhaitées (Highly Desired Skills)
- Expérience de travail avec des plateformes d'orchestration de données (par exemple, Apache Airflow).
- Connaissance des concepts d'entrepôt de données (par exemple, Databricks, Snowflake, Redshift).
- Expérience de travail dans un environnement réglementé (par exemple, soins de santé, finance).
- Expérience avec les outils d'Infrastructure as Code (IaC) comme Terraform.

## Attributs Personnels
- Courage : Volonté de s'exprimer, de remettre en question le statu quo et d'accepter de nouveaux défis.
- Humilité : Ouverture à l'apprentissage, à la recherche d'aide en cas de besoin et une concentration sur le service aux autres.
- Aventure : Une passion pour fixer des objectifs ambitieux, s'attaquer à des tâches difficiles et trouver de la joie dans le voyage.
- Initiative : Résolution proactive des problèmes, un sens de l'appropriation et une volonté d'aller au-delà des attentes.
- Résilience : La capacité à rebondir après des revers, à persévérer face aux défis et à en ressortir plus fort.
MARKDOWN,
            ],
            'data_scientist' => [
                'title' => 'Data Scientist',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée. Ces données sont gérées dans notre Centre d'Intelligence Sanitaire (CIS) en utilisant une architecture de données de pointe.

Nous sommes à la recherche d'un(e) Scientifique des Données (Data Scientiste) dont le rôle principal consistera à exploiter les informations basées sur les données pour résoudre des problèmes complexes et prendre des décisions commerciales axées sur les données. Il/Elle travaillera en étroite collaboration avec les parties prenantes, les ingénieurs de données et les analystes pour analyser de grands ensembles de données, construire des modèles prédictifs et développer des algorithmes d'apprentissage automatique. Par conséquent, son expertise en analyse statistique, en apprentissage automatique et en programmation est essentielle pour extraire des informations significatives et fournir des recommandations exploitables pour atteindre les objectifs commerciaux.

## Responsabilités (Responsibilities)
- **Analyse de Données :** Effectuer une analyse exploratoire des données (EDA) pour comprendre les tendances, les modèles et les relations au sein de grands ensembles de données.
- **Modélisation en Apprentissage Automatique :** Développer et déployer des modèles d'apprentissage automatique pour la prédiction, la classification, le clustering et la recommandation en utilisant des algorithmes tels que la régression, les arbres de décision, les forêts aléatoires, les réseaux neuronaux, et autres.
- **Analyse statistique :** Appliquer des méthodes statistiques et des tests d'hypothèses pour valider les conclusions et prendre des décisions basées sur les données.
- **Préparation des données et Ingénierie des Caractéristiques (Feature Engineering) :** Nettoyer, prétraiter et transformer les données brutes en formats utilisables pour la modélisation, y compris la sélection et l'ingénierie des caractéristiques.
- **Évaluation et optimisation des Modèles :** Évaluer les métriques de performance des modèles et optimiser les algorithmes pour la précision, l'efficacité et l'évolutivité.
- **Visualisation des Données :** Créer des visualisations de données, des graphiques et des tableaux de bord informatifs et visuellement attrayants pour communiquer efficacement les résultats.
- **Communication des Résultats :** Documenter et présenter les méthodologies, les résultats et les implications des modèles d'apprentissage automatique aux parties prenantes.

## Compétences et Qualifications
- Expérience professionnelle en tant que Scientifique des Données ou dans un rôle similaire, avec une expérience pratique en analyse de données, en apprentissage automatique et en modélisation statistique.
- Fortes compétences en résolution de problèmes et en analyse avec la capacité d'extraire des informations à partir d'ensembles de données complexes.
- Excellentes compétences en communication et en présentation pour transmettre des concepts techniques et des découvertes à des parties prenantes non techniques.
- Capacité à travailler de manière autonome et en collaboration dans un environnement d'équipe, en s'adaptant aux exigences et aux priorités changeantes des projets.

## Langages/Outils Souhaitables (Desirable Languages/Tools)
- Maîtrise des langages de programmation tels que Python, R ou Scala pour la manipulation de données, l'analyse statistique et le développement de modèles d'apprentissage automatique.
- Forte compréhension des algorithmes, techniques et bibliothèques d'apprentissage automatique (par exemple, scikit-learn, TensorFlow, PyTorch).
- Expérience avec des outils de visualisation de données tels que Tableau, Power BI ou matplotlib/seaborn en Python.
- Connaissance des méthodes d'analyse statistique, des tests d'hypothèses et de la conception expérimentale.
- Familiarité avec les bases de données et SQL pour l'interrogation et la manipulation des données.

## Liste des Outils Spécifiques :
- Python (pandas, numpy, scikit-learn, TensorFlow, PyTorch)
- R (dplyr, ggplot2, caret, TensorFlow, Keras)
- SQL (pour l'interrogation et la manipulation des données)
- Apache Spark (pour le traitement distribué des données)
- Tableau, Power BI, matplotlib/seaborn (pour la visualisation des données)
- Jupyter Notebook ou RStudio (pour l'analyse interactive des données)
- Git (contrôle de version)
- L'expérience avec les plateformes et services cloud (par exemple, AWS, Azure, Google Cloud Platform) est un atout.
MARKDOWN,
            ],
            'platform_engineer' => [
                'title' => 'Ingénieur de Plateforme (Platform Engineer)',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
En tant qu'Ingénieur de Plateforme chez Sand Technologies, vous jouerez un rôle central dans la construction et le maintien des plateformes sous-jacentes et de l'infrastructure qui permettent à nos équipes d'ingénierie et de science des données de développer, déployer et exploiter nos solutions d'IA à grande échelle et de manière fiable.

Ce rôle se concentre sur l'amélioration de la vélocité des développeurs en fournissant des outils et des services en libre-service (self-service), tout en garantissant la sécurité, l'évolutivité et la rentabilité de notre infrastructure cloud. Vous serez le pont entre le développement logiciel et les opérations informatiques, en promouvant la culture DevOps et les principes SRE (Site Reliability Engineering).

## Responsabilités Clés (Key Responsibilities)
### Ingénierie de l'Infrastructure et Cloud :
- Concevoir, mettre en œuvre et maintenir l'infrastructure cloud (AWS, Azure, GCP) à l'aide de l'Infrastructure as Code (IaC), principalement avec Terraform.
- Gérer et optimiser les ressources de calcul, de réseau et de stockage pour garantir l'évolutivité, la haute disponibilité et la résilience.

### Plateformes Conteneurisées et Orchestration :
- Déployer et gérer des plateformes d'orchestration de conteneurs, principalement Kubernetes (K8s), pour les charges de travail de développement et de production.
- Mettre en œuvre des pratiques de maillage de services (service mesh) et de gestion des identités dans l'environnement conteneurisé.

### CI/CD et Automatisation :
- Concevoir et maintenir des pipelines de Continuous Integration et Continuous Deployment (CI/CD) robustes et efficaces, en utilisant des outils comme Jenkins, GitLab CI ou GitHub Actions.
- Développer des outils d'automatisation pour simplifier les tâches opérationnelles et améliorer la vélocité des développeurs.

### Surveillance et Observabilité (Monitoring & Observability) :
- Mettre en œuvre et gérer des systèmes de surveillance complets (par exemple, Prometheus, Grafana, ELK/Loki) pour suivre les performances de l'infrastructure, des applications et des pipelines de données.
- Développer des stratégies d'alerte et de journalisation pour garantir une réponse rapide aux incidents.

### Sécurité et Conformité :
- Mettre en œuvre et faire respecter les meilleures pratiques de sécurité de l'infrastructure et de l'application (par exemple, gestion des secrets, politique de moindre privilège).
- Assurer la conformité aux exigences réglementaires et aux normes de l'industrie (par exemple, HIPAA, GDPR, ISO).

## Qualifications et Expérience
- Formation : Diplôme universitaire en Informatique, Ingénierie, ou domaine technique connexe.
- Expérience : 4 années et plus d'expérience professionnelle dans un rôle DevOps, SRE ou Ingénieur de Plateforme.
- Cloud : Expertise avérée avec au moins un fournisseur de cloud majeur (AWS, Azure ou GCP).
- IaC : Solide maîtrise de l'Infrastructure as Code, en particulier Terraform.
- Conteneurs : Expérience approfondie avec Docker et Kubernetes (y compris la gestion des clusters, la mise en réseau et le stockage).
- CI/CD : Expérience dans la construction et l'optimisation des pipelines de livraison continue.
- Programmation : Solide maîtrise d'au moins un langage de script ou de programmation (Python ou Bash est préférable).

## Compétences Hautement Souhaitées (Highly Desired Skills)
- Expérience avec les pratiques GitOps (par exemple, ArgoCD, Flux).
- Expérience dans le secteur de la santé ou de l'analyse de données à grande échelle.
- Connaissance des maillages de services (Service Meshes) tels que Istio ou Linkerd.
- Expérience avec des bases de données NoSQL (par exemple, MongoDB, Cassandra).

## Attributs Personnels
- Courage : Volonté de s'exprimer, de remettre en question le statu quo et d'accepter de nouveaux défis.
- Humilité : Ouverture à l'apprentissage, à la recherche d'aide en cas de besoin et une concentration sur le service aux autres.
- Aventure : Une passion pour fixer des objectifs ambitieux, s'attaquer à des tâches difficiles et trouver de la joie dans le voyage.
- Initiative : Résolution proactive des problèmes, un sens de l'appropriation et une volonté d'aller au-delà des attentes.
- Résilience : La capacité à rebondir après des revers, à persévérer face aux défis et à en ressortir plus fort.
MARKDOWN,
            ],
            'program_lead' => [
                'title' => 'Gestionnaire de programme (Programme lead)',
                'description' => <<<'MARKDOWN'
## À propos du Rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée. Ces données sont gérées dans notre Centre d'Intelligence Sanitaire (CIS) en utilisant une architecture de données de pointe.

Nous sommes à la recherche d’un(e) Chef de Programme (Program Lead) du Centre d'Intelligence Sanitaire (CIS). Il/Elle sera le pilier stratégique et opérationnel de toutes les activités du Centre d'Intelligence Sanitaire.

Ce rôle combine la gestion des solutions, le leadership opérationnel, la supervision technique et l'engagement des parties prenantes pour garantir que les CIS fournissent des informations de santé exploitables et renforcent les systèmes de santé. Le/La Chef de Programme agit à la fois comme un(e) représentant(e) national(e) et un(e) champion(ne) des solutions, en partenariat étroit avec les Ministères de la Santé, le personnel des établissements et les équipes centrales.

## Responsabilités Clés
### Gestion des Solutions et Livraison de Produits
- Assumer la responsabilité et piloter le carnet de produit (product backlog) pour les systèmes et solutions du CIS.
- Gérer la configuration, les tests et le déploiement des outils dans les établissements ruraux et les centres d'intelligence.
- Servir de premier défenseur des utilisateurs du CIS, capturant les commentaires et identifiant les lacunes en matière d'ergonomie, d'intégration des flux de travail et de performance.
- Effectuer des visites sur le terrain pour comprendre les besoins des utilisateurs, des Agents de Santé Communautaire (ASC) aux Ministres de la Santé.
- Diriger la formation des utilisateurs et la gestion du changement, en veillant à ce que le personnel de première ligne puisse adopter et utiliser efficacement les outils numériques.
- Collaborer avec les équipes centrales de produits et d'ingénierie pour prioriser les corrections, les améliorations de fonctionnalités et les optimisations du système.
- Soutenir le développement de cas d'utilisation de valeur en liant les fonctionnalités du produit à des indicateurs d'impact mesurables.

### Supervision Technique et Opérations
- Fournir des orientations stratégiques sur l'infrastructure, les pipelines de données, l'analyse et les intégrations de systèmes.
- S'assurer que les équipes techniques suivent les meilleures pratiques en matière de CI/CD, ETL, qualité des données, observabilité et sécurité.
- Superviser les opérations quotidiennes du CIS, l'optimisation des flux de travail, l'allocation des ressources et le suivi des performances.
- Intégrer la Livraison Simultanée + le Renforcement des Capacités : obtenir des résultats tout en renforçant les compétences de l'équipe.

### Gestion des Parties Prenantes et des Partenaires
- Servir de point de contact principal pour les Ministères de la Santé, les responsables d'établissements et les partenaires externes.
- Traduire les informations techniques et analytiques en recommandations claires et exploitables.
- Établir des relations solides, favorisant la collaboration entre les équipes, les établissements et les parties prenantes gouvernementales.

### Renforcement des Capacités et Leadership d'Équipe
- Encadrer, mentorer et guider les membres des équipes techniques, analytiques et opérationnelles.
- Promouvoir une culture d'apprentissage continu, de certification et de croissance professionnelle.
- S'assurer que les livrables du CIS soutiennent l'excellence opérationnelle, la prise de décision éclairée et l'impact sur le système de santé.

### Gestion des Connaissances et Documentation
- Contribuer aux guides opérationnels internes (playbooks), à la documentation produit et au matériel destiné aux utilisateurs.
- Assurer que les connaissances sont codifiées, partagées et utilisées pour l'amélioration continue.

## Compétences et Aptitudes Requises
- Compréhension approfondie des systèmes d'information sanitaire, de la gestion des données de santé et des flux de travail de la santé numérique.
- Capacité démontrée à synthétiser les commentaires des utilisateurs en informations exploitables.
- Solides compétences en leadership et en coordination interfonctionnelle.
- Excellentes capacités d'engagement des parties prenantes, de facilitation et de formation.
- Penseur(se) stratégique avec de solides compétences opérationnelles et de résolution de problèmes.
- Capacité à travailler dans des environnements à faibles ressources ou ruraux, en maintenant des boucles de rétroaction étroites avec des équipes distribuées.
- Familiarité avec des plateformes telles que DHIS2, OpenMRS, CommCare ou similaires.

## Qualifications
- Diplôme supérieur en Santé Publique, Informatique de la Santé, Science des Données ou domaine connexe.
- 10 ans et plus d'expérience en TI pour la santé, systèmes de DSE (Dossier de Santé Électronique) ou livraison de santé numérique, idéalement dans des contextes à faibles ressources.
- Expérience démontrée dans la gestion d'équipes multidisciplinaires, de déploiements ou de programmes de santé complexes.
- Bilingue (Anglais + Français) pour les pays francophones.
MARKDOWN,
            ],
            'senior_data_engineer' => [
                'title' => 'Ingénieur de Données Senior (Sr Data Engineer)',
                'description' => <<<'MARKDOWN'
## À propos du rôle
Le Système d'Exploitation de la Santé (HOS - Health Operating System) est un projet de Sand Technologies qui réinvente la prestation des soins de santé à travers l'Afrique. HOS intègre de manière transparente la technologie, les personnes et les processus pour recueillir des données de haute qualité à partir des postes de santé, donnant aux responsables de la santé des données en temps réel pour une prise de décision éclairée. Ces données sont gérées dans notre Centre d'Intelligence Sanitaire (HIC) en utilisant une architecture de données de pointe.

Nous recherchons un(e) Ingénieur(e) de Data Senior pour être un membre clé de notre équipe technologique. Vous travaillerez sur l'ingénierie des données pour HOS, en vous concentrant sur la conception, la mise en œuvre et la maintenance de pipelines de données fiables, efficaces et évolutifs. Ce rôle implique la construction et la gestion de systèmes de données qui traitent des données cliniques et opérationnelles sensibles, garantissant la qualité des données, la sécurité et la conformité aux normes réglementaires.

## Responsabilités Clés (Key Responsibilities)
### Conception et Développement des Pipelines de Données :
- Concevoir et développer des pipelines ETL/ELT robustes, évolutifs et tolérants aux pannes pour déplacer et transformer des données de santé volumineuses (structurées et non structurées) depuis des systèmes de dossiers médicaux électroniques (DSE/EMR), des appareils IoT et d'autres sources de données sanitaires.
- Développer des flux de travail de données en utilisant des technologies Big Data et Cloud.
- Mettre en œuvre des processus d'ingénierie de données garantissant la haute performance et la faible latence des requêtes.

### Architecture et Modélisation de la Base de Données :
- Collaborer avec les architectes de données pour concevoir des modèles de données dimensionnels et relationnels dans l'entrepôt de données Sand HIC.
- Assurer que l'architecture de données soutient efficacement l'analyse en temps réel, les rapports et les applications d'IA/Machine Learning.

### Qualité et Sécurité des Données :
- Établir et maintenir des normes de qualité des données, y compris des cadres de validation, de surveillance et de signalement des anomalies.
- Veiller à ce que tous les pipelines et systèmes de données respectent les protocoles de confidentialité des données (tels que la pseudonymisation et le chiffrement) et les normes de conformité (telles que le HIPAA/GDPR).

### Optimisation et Opérations :
- Surveiller, optimiser et ajuster les systèmes de données pour améliorer les performances, réduire les coûts et garantir la fiabilité.
- Mettre en œuvre des stratégies d'observabilité, de journalisation et d'alerte.

### Collaboration et Leadership :
- Travailler en étroite collaboration avec les ingénieurs logiciels, les scientifiques des données et les analystes pour comprendre les besoins en données et fournir des solutions.
- Fournir des orientations techniques et encadrer les ingénieurs de données juniors.

## Qualifications et Expérience
- Formation : Diplôme universitaire en Informatique, Ingénierie, Science des Données ou domaine connexe.
- Expérience : 6 années et + d'expérience professionnelle dans un rôle d'Ingénieur de Données ou d'Ingénieur Logiciel axé sur les données.
- Ingénierie de données : Expérience avérée dans la conception, la construction et la maintenance de pipelines de données à grande échelle.
- SQL : Maîtrise de SQL avancé et expérience avec des bases de données relationnelles (par exemple, PostgreSQL, MySQL).
- Programmation : Solide maîtrise d'au moins un langage de programmation (idéalement Python) pour l'ingénierie des données.
- Cloud et Big Data : Expérience avec des services cloud (AWS, Azure ou GCP) et des technologies Big Data (par exemple, Apache Spark, Databricks, Snowflake ou équivalent).

## Compétences Hautement Souhaitées (Highly Desired Skills)
- Expérience de travail dans le domaine de l'informatique de la santé ou avec des données de santé (par exemple, DSE, HL7, FHIR).
- Expérience avec des outils d'orchestration de données tels que Apache Airflow, Informatica ou Talend.
- Connaissance des pipelines CI/CD, de Git et de la modélisation des données (relationnelle et dimensionnelle).

## Attributs Personnels
- Courage : Volonté de s'exprimer, de remettre en question le statu quo et d'accepter de nouveaux défis.
- Humilité : Ouverture à l'apprentissage, à la recherche d'aide en cas de besoin et une concentration sur le service aux autres.
- Aventure : Une passion pour fixer des objectifs ambitieux, s'attaquer à des tâches difficiles et trouver de la joie dans le voyage.
- Initiative : Résolution proactive des problèmes, un sens de l'appropriation et une volonté d'aller au-delà des attentes.
- Résilience : La capacité à rebondir après des revers, à persévérer face aux défis et à en ressortir plus fort.
MARKDOWN,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (array $position, string $key): array => [$key => $position['title']])
            ->all();
    }

    public static function title(?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        return self::all()[$key]['title'] ?? $key;
    }
}
