Prompt1 GEMINI 
pour la Conception d'un Système de Billetterie Intelligent pour Compagnie de Transport

  Objectif Général

  Créer une application web futuriste et intelligente pour la gestion et la vente de billets de transport. Le système doit
  optimiser le placement des passagers dans un véhicule en fonction de leur point de descente afin de minimiser les temps
  d'arrêt et d'améliorer l'expérience de tous les voyageurs.

  Contexte et Utilisateurs

   * Utilisateurs principaux : Les agents de billetterie dans les gares routières.
   * Environnement : L'interface sera utilisée sur des ordinateurs de bureau, potentiellement avec des écrans tactiles.
   * Clientèle : Les compagnies de transport qui opèrent des trajets avec des arrêts intermédiaires.

  Concepts Clés

   1. Trajet (Trip) : Un itinéraire complet défini par un point de départ (A) et un point d'arrivée (B).
   2. Tronçon (Leg/Segment) : La portion d'un trajet entre deux arrêts consécutifs.
   3. Point d'Arrêt (Stop) : Un point intermédiaire sur un trajet où des passagers peuvent monter ou descendre. Chaque point
      d'arrêt est défini par ses coordonnées géographiques (Latitude, Longitude).
   4. Véhicule (Vehicle) : Représentation d'un bus ou d'un car avec une configuration de sièges spécifique.
   5. Placement Intelligent (Smart Seating) : La fonctionnalité centrale qui consiste à assigner des sièges de manière
      stratégique.

  Fonctionnalités Principales

  1. Module d'Administration
   * Gestion des Trajets :
       * CRUD (Créer, Lire, Mettre à jour, Supprimer) pour les trajets.
       * Ajouter des points d'arrêt intermédiaires à un trajet en spécifiant leur nom et leurs coordonnées (Lat/Lon).
       * Le système doit calculer et afficher automatiquement la distance de chaque point d'arrêt depuis le point de départ.
   * Gestion des Véhicules :
       * Permettre aux administrateurs de créer différents types de véhicules (ex: "Bus 50 places", "Minibus 18 places").
       * Pour chaque type de véhicule, fournir un outil pour dessiner ou importer une représentation visuelle (SVG) de la
         disposition des sièges.
       * Chaque siège sur le SVG doit être un objet cliquable avec un identifiant unique (ex: "1A", "1B", "2A"...).

  2. Interface de Vente de Billets (Pour les Agents)
   * Sélection du Voyage :
       * L'agent sélectionne le trajet (ex: "Douala -> Yaoundé") et l'heure de départ.
   * Sélection de la Destination :
       * L'agent sélectionne le point de départ et le point de descente du passager parmi les arrêts disponibles sur le trajet.
   * Visualisation Intelligente du Véhicule :
       * Le système affiche la représentation SVG du véhicule assigné à ce départ.
       * Color-Coding : Les sièges sont colorés en fonction de la destination des passagers déjà enregistrés.
           * Sièges occupés : Une couleur distincte pour chaque tronçon/point de descente. Par exemple :
               * Arrêt 1 (le plus proche) : Vert
               * Arrêt 2 : Jaune
               * Arrêt 3 : Orange
               * Destination finale : Rouge
           * Sièges disponibles : Gris
           * Sièges bloqués/inutilisables : Noir
       * Suggestion de Placement (Le cœur de l'intelligence) :
           * En fonction de la destination du nouveau passager, le système doit suggérer automatiquement le siège optimal.
           * Logique de suggestion : Pour un passager qui descend à un arrêt proche, le système propose le siège disponible le
             plus proche de la sortie. Pour un passager allant à la destination finale, il propose un siège plus au fond.
           * La suggestion peut prendre la forme d'un siège qui clignote ou qui est mis en surbrillance.
   * Assignation du Siège :
       * L'agent peut accepter la suggestion en un clic ou la modifier manuellement en cliquant sur un autre siège gris
         disponible (flexibilité essentielle).
   * Finalisation de la Vente :
       * Une fois le siège confirmé, l'agent remplit les informations du passager (nom, contact).
       * Le système génère un billet électronique (avec QR code) contenant les informations : nom du passager, trajet, heure,
         numéro de véhicule, et numéro de siège.

  Stack Technique

   * Backend : Laravel
       * API RESTful pour gérer les trajets, véhicules, ventes, et passagers.
       * Utiliser la librairie geotools-laravel ou un calcul Haversine pour les distances.
   * Frontend : Next.js (React)
       * Interface réactive et dynamique pour les agents.
       * Utilisation de SVG interactifs (ex: avec la librairie react-svg ou directement en manipulant le DOM).
       * Communication avec le backend via des appels API.
   * Base de Données : MySQL ou PostgreSQL
       * Tables pour trips, stops, vehicles, seat_layouts, bookings, passengers, tickets.

  Scénario d'Utilisation Concret

   1. Configuration : Un admin a configuré le trajet "Cotonou -> Parakou" avec les arrêts intermédiaires "Bohicon" (100km) et
      "Dassa" (200km). Il a aussi configuré un "Bus 54 places" avec un plan SVG.
   2. Vente : Un passager arrive et veut un billet de "Cotonou" pour "Bohicon".
   3. Action de l'agent :
       * Il sélectionne le départ de 8h00 pour "Cotonou -> Parakou".
       * Il choisit la destination du passager : "Bohicon".
   4. Affichage du système :
       * L'interface affiche le plan du bus.
       * Les sièges des passagers allant jusqu'à "Parakou" sont en rouge.
       * Les sièges des passagers pour "Dassa" sont en orange.
       * Le système fait clignoter en vert le siège 2A, un siège près de la porte, car c'est l'optimal pour quelqu'un qui
         descend à "Bohicon".
   5. Décision : L'agent confirme le siège 2A, encaisse le paiement et imprime le billet. Le siège 2A devient vert sur
      l'interface pour les prochaines ventes.
--------------------------------------------------------------------
Prompt2 GPT
Contexte : je veux concevoir un système de billetterie futuriste et intelligent pour une compagnie de transport interurbain. Tech stack : Frontend React / Next.js (PWA, tactile-friendly), Backend Laravel (API REST/GraphQL).
Objectif : lorsqu’un agent vend un billet d’un point A vers un point B avec arrêts intermédiaires, le système propose automatiquement un placement optimal des passagers dans le véhicule en fonction de la proximité de leur arrêt, des contraintes (arrêts prioritaires comme ports) et du gain de temps collectif. L’interface doit permettre un ajustement tactile via une représentation du véhicule (SVG), coloration par tronçon, et actions directes (glisser-déposer / toucher).

Fonctionnalités requises & contraintes techniques :

Géométrie & distances

Calculer distances réelles (great-circle / haversine) entre lat/lon pour : A, B, et tous les arrêts intermédiaires.

Générer tronçons (segments) entre arrêts et calculer longueur cumulée.

Allocation intelligente des billets

Algorithme : allouer places par proximité descendante (les passagers qui sortent le plus tôt sont placés près de la porte correspondante).

Prendre en compte : temps d’embarquement/débarquement estimé, arrêts « prioritaires » (ex. ports → flag priority_stop), contraintes d’accessibilité (ex. sièges PMR), et cohérence de groupe (familles ensembles).

Résultat attendu : pour chaque billet → position(s) de siège/sélection de zone (ex. rangée X, côté gauche) + couleur de tronçon.

Interface véhicule (SVG)

Vue top-down ou latérale du véhicule en SVG générée dynamiquement selon le modèle de véhicule (mini-bus, bus articulé, car).

Zones de sièges cliquables/tactiles, surlignage du tronçon associé (couleurs distinctes).

Actions tactiles : assigner / réassigner billet par toucher ou glisser; vue « optimisation automatique » qui réorganise en un clic.

UX / couleurs

Assigner une couleur unique par tronçon ; gradient ou teintes cohérentes pour faciliter la lecture.

Indicateurs : temps estimé avant descente, priorité d’arrêt, siège réservé/occupé/PMR.

API / Backend

Endpoints essentiels : GET /trips/{id} (avec arrêts + lat/lon), POST /trips/{id}/allocate (entrée : liste de billets + contraintes → sortie : plan de sièges), PATCH /allocations/{id}, GET /vehicle-models, POST /tickets.

Traitement en back : allocation par job (synchronous small trips, sinon job/queue) ; possibilité d’optimisation côté client pour réassignement rapide (mais validation server-side).

Performance & exploitation

UI doit être réactive sur écran tactile en gare (offline-friendly partielle, PWA), réseau instable : cache local, réconciliation quand online.

Pour grandes listes (plusieurs centaines de passagers), paginer, charger par tronçon, et envoyer seulement diffs pour WebSocket updates.

Sécurité & conformité

Auth + rôles : billeteur, supervisor, admin. Logs d’audit pour toute réaffectation manuelle.

Livrables & critères d’acceptation

Maquette fonctionnelle (Next.js) montrant : sélection d’un trajet, SVG du véhicule, allocation automatique, réassignement tactile, coloration par tronçon.

API Laravel documentée (OpenAPI) avec tests unitaires pour l’algorithme d’allocation.

Exemple d’entrée/sortie pour l’endpoint d’allocation (voir plus bas).

Exemple JSON d’entrée pour l’API d’allocation :

{
  "trip": {
    "id":"trip_001",
    "stops":[
      {"id":"A","lat":..., "lon":...},
      {"id":"P1","lat":..., "lon":..., "priority":false},
      {"id":"P2","lat":..., "lon":..., "priority":true},
      {"id":"P3","lat":..., "lon":..., "priority":false},
      {"id":"B","lat":..., "lon":...}
    ],
    "vehicle_model":"coach_52"
  },
  "tickets":[
    {"id":"t1","passenger":"Alice","stop_id":"P1","group_id":null,"needs_pmr":false},
    {"id":"t2","passenger":"Bob","stop_id":"P2","group_id":"fam42","needs_pmr":true}
  ],
  "options": {"optimize_for":"min_total_open_door_time"}
}


Sortie attendue : mapping ticket → siège (row, col), couleur de tronçon, score d’optimisation.

Tests d’acceptation rapides :

si 3 passagers descendent successivement à P1, P2, P3, ils doivent être placés du plus proche de la sortie à l’avant (ou côté porte), minimisant le flux piéton.

si un arrêt est priority: true (port), placer ses passagers dans des sièges proches pour débarquer plus rapidement.

Livrer : maquettes UI (SVG interactif), code frontend Next.js minimal, endpoints Laravel stub + tests unitaires de l’algorithme.
---------------------------------------------------------------
PROMPT3 (claude) - Système de Billetterie Intelligente 

## INSTRUCTION PRINCIPALE
Construire un MVP (Minimum Viable Product) d'un système de billetterie intelligent pour transport inter-urbain avec optimisation automatique des places basée sur les destinations.

## PRIORITÉS DE DÉVELOPPEMENT (À suivre dans l'ordre)

### PHASE 1 - FONDATIONS (2-3 semaines)
**Objectif : Système de base fonctionnel**

**Backend Laravel :**
```
1. Créer API REST avec authentification JWT + Redis Cache
2. Implémenter 6 tables principales :
   - gares (id, nom, latitude, longitude)
   - trajets (id, nom, gare_depart, gare_arrivee)
   - troncons (id, trajet_id, gare_origine, distance_km, tarif)
   - vehicules (id, type, capacite, plan_svg, config_portes)
   - voyages (id, trajet_id, vehicule_id, date, heure_depart)
   - reservations (id, voyage_id, passager, siege, troncon_id, statut)

3. Endpoints critiques :
   - GET /api/trajets (liste des trajets disponibles)
   - GET /api/voyages/{trajet_id}/{date} (voyages du jour)
   - POST /api/reservations (nouvelle réservation)
   - GET /api/vehicules/{id}/plan (plan du véhicule avec config)
   - POST /api/optimisation/{voyage_id} (calcul placement optimal)
   - GET /api/dashboard/stats (stats temps réel pour supervision)
```

**Frontend Next.js :**
```
1. Interface billetteur robuste :
   - Login avec rôles (billetteur/superviseur)
   - Dashboard temps réel (occupation véhicules)
   - Sélection trajet + voyage + véhicule
   - Plans véhicules SVG adaptatifs (4 templates)
   - Attribution avec suggestions multiples
   - Gestion files d'attente (50 utilisateurs simultanés)

2. Composants React prioritaires :
   - TrajetSelector avec recherche
   - VoyageSelector (horaires du jour)
   - VehiclePlanDynamic (adapté au type de véhicule)
   - SeatOptimizer (suggestions intelligentes)
   - ReservationQueue (gestion concurrence)
   - StatsRealTime (monitoring superviseur)
```

**Livrables Phase 1 :**
- Réservation fonctionnelle pour les 4 types de véhicules
- Plans SVG adaptatifs (Bus 15, 30, 50 places, Bus articulé 80)
- Système de cache Redis pour performance
- Base de données peuplée avec 10 gares, 5 trajets, 12 véhicules
- Gestion concurrence 50 utilisateurs simultanés
- Dashboard supervision temps réel

### PHASE 2 - INTELLIGENCE (2 semaines)
**Objectif : Optimisation automatique**

**Algorithme d'optimisation multi-véhicules :**
```php
// Laravel - Classe OptimisationService
public function calculateOptimalSeats($voyage_id, $troncon_destination) {
    // 1. Identifier type véhicule (Bus 15/30/50/80 places)
    // 2. Charger configuration portes spécifique
    // 3. Récupérer sièges libres avec cache Redis
    // 4. Calculer score par siège selon type véhicule :
    //    - Bus 15 : 1 porte avant
    //    - Bus 30/50 : 2 portes (avant/milieu)  
    //    - Bus articulé 80 : 3 portes (avant/milieu/arrière)
    // 5. Retourner top 5 sièges recommandés
    // 6. Mettre à jour cache occupation
}
```

**Interface améliorée :**
```
- Sélection automatique type véhicule selon trajet
- Plans SVG dynamiques selon capacité véhicule
- Suggestions automatiques adaptées (top 5 sièges)
- Visualisation temps réel des zones de couleur
- Override manuel avec validation conflit
- Statistiques d'occupation par type véhicule
- Dashboard supervision multi-gares
```

### PHASE 3 - FINITION (1 semaine)
**Objectif : Production ready**

```
- Tests unitaires critiques
- Documentation API
- Interface mobile responsive
- Export données basique (CSV)
```

## SPÉCIFICATIONS TECHNIQUES OBLIGATOIRES

### Contraintes Système
- **Volume production** : 2000 réservations/jour
- **Utilisateurs simultanés** : 50 billetteurs maximum
- **Types de véhicules** : 4 types différents (Bus 30, Bus 50, Bus articulé 80, Minibus 15)
- **Performance** : Temps réponse < 200ms sous charge
- **Intégrations** : Aucune (Phase 1) - fichiers CSV export seulement

### Stack Technique FIXE
```
Backend : Laravel 10 + MySQL 8 + Redis 6 (Cache & Sessions)
Frontend : Next.js 13 + React 18 + Tailwind CSS + WebSockets
SVG : Bibliothèque D3.js + templates adaptatifs par véhicule
Authentication : Laravel Sanctum + JWT
Cache : Redis pour sessions et données véhicules
Queue : Laravel Horizon pour réservations simultanées
```

### Structure de Données OBLIGATOIRE
```sql
-- Véhicules avec types multiples
CREATE TABLE vehicules (
    id VARCHAR(10) PRIMARY KEY,
    immatriculation VARCHAR(20),
    type ENUM('minibus_15', 'bus_30', 'bus_50', 'bus_articule_80'),
    capacite_totale INT,
    configuration_sieges VARCHAR(10), -- "2+1", "2+2", etc.
    nombre_portes INT,
    positions_portes JSON, -- [1, 15] ou [1, 25, 60]
    plan_svg_template VARCHAR(50),
    actif BOOLEAN DEFAULT true
);

-- Voyages (instances de trajets avec véhicule et horaire)
CREATE TABLE voyages (
    id VARCHAR(15) PRIMARY KEY,
    trajet_id VARCHAR(10),
    vehicule_id VARCHAR(10),
    date DATE,
    heure_depart TIME,
    statut ENUM('programme', 'embarquement', 'parti', 'arrive'),
    FOREIGN KEY (trajet_id) REFERENCES trajets(id),
    FOREIGN KEY (vehicule_id) REFERENCES vehicules(id)
);

-- Réservations avec gestion concurrence
CREATE TABLE reservations (
    id VARCHAR(15) PRIMARY KEY,
    voyage_id VARCHAR(15),
    numero_siege INT,
    troncon_id VARCHAR(10),
    nom_passager VARCHAR(100),
    telephone VARCHAR(20),
    prix_paye DECIMAL(8,2),
    billetteur_id VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('reserve', 'confirme', 'embarque', 'annule'),
    UNIQUE KEY unique_siege_voyage (voyage_id, numero_siege),
    FOREIGN KEY (voyage_id) REFERENCES voyages(id)
);

-- (Autres tables déjà définies : gares, trajets, troncons)
```

## ALGORITHME D'OPTIMISATION PAR TYPE DE VÉHICULE

### Logique Multi-Véhicules (Phase 1)
```
MINIBUS 15 places (config 2+1, 1 porte avant):
├─ Tronçon T1 (50km): Sièges 1-5 (ROUGE) - Score = 10-distance_porte
├─ Tronçon T2 (100km): Sièges 6-10 (ORANGE) - Score = 8-distance_porte  
└─ Tronçon T3 (150km): Sièges 11-15 (VERT) - Score = 6-distance_porte

BUS 30 places (config 2+2, 2 portes: avant+milieu):
├─ Tronçon T1: Sièges 1-8, 16-20 (ROUGE) - Portes position 1 et 16
├─ Tronçon T2: Sièges 9-15, 21-25 (ORANGE)
└─ Tronçon T3: Sièges 26-30 (VERT)

BUS 50 places (config 2+2, 2 portes: avant+milieu):
├─ Tronçon T1: Sièges 1-12, 26-30 (ROUGE) - Portes position 1 et 26
├─ Tronçon T2: Sièges 13-25, 31-40 (ORANGE)  
└─ Tronçon T3: Sièges 41-50 (VERT)

BUS ARTICULÉ 80 places (config 2+2, 3 portes: avant+milieu+arrière):
├─ Tronçon T1: Sièges 1-15, 35-40, 65-70 (ROUGE) - 3 portes
├─ Tronçon T2: Sièges 16-34, 41-64 (ORANGE)
└─ Tronçon T3: Sièges 71-80 (VERT)
```

## DONNÉES DE TEST OBLIGATOIRES

### Véhicules à créer (4 types) :
```json
[
  {
    "id": "MIN01", "type": "minibus_15", "immatriculation": "DK-1501-AB", 
    "capacite": 15, "config": "2+1", "portes": [1], "plan": "minibus_15.svg"
  },
  {
    "id": "BUS01", "type": "bus_30", "immatriculation": "DK-3001-CD",
    "capacite": 30, "config": "2+2", "portes": [1, 16], "plan": "bus_30.svg"
  },
  {
    "id": "BUS02", "type": "bus_50", "immatriculation": "DK-5001-EF", 
    "capacite": 50, "config": "2+2", "portes": [1, 26], "plan": "bus_50.svg"
  },
  {
    "id": "ART01", "type": "bus_articule_80", "immatriculation": "DK-8001-GH",
    "capacite": 80, "config": "2+2", "portes": [1, 35, 65], "plan": "bus_80.svg"
  }
]
```

### Trajets à créer (avec véhicules assignés) :
```
Trajet 1: Dakar → Thiès (Bus 50 places - BUS02)
- T1: Dakar → Rufisque (50km, 1500 FCFA)
- T2: Dakar → Bargny (100km, 2500 FCFA)  
- T3: Dakar → Thiès (150km, 3500 FCFA)

Trajet 2: Dakar → Kaolack (Bus articulé 80 - ART01)  
- T1: Dakar → Fatick (80km, 2000 FCFA)
- T2: Dakar → Kaolack (180km, 4000 FCFA)

Trajet 3: Centre-ville circuits (Minibus 15 - MIN01)
- T1: Plateau → Médina (15km, 500 FCFA)
- T2: Plateau → Parcelles (25km, 750 FCFA)
```

## CRITÈRES DE RÉUSSITE MVP

### Fonctionnalités OBLIGATOIRES
- [ ] Billetteur peut sélectionner trajet ET véhicule assigné
- [ ] Plans SVG adaptatifs pour 4 types véhicules (15/30/50/80 places)
- [ ] Codage couleur par tronçon adapté au véhicule
- [ ] Gestion concurrence : 50 billetteurs simultanés (Redis locks)
- [ ] Algorithme suggère 5 sièges optimaux selon type véhicule
- [ ] Dashboard supervision temps réel (occupation par véhicule)
- [ ] Export CSV des 2000 réservations quotidiennes
- [ ] WebSockets pour mises à jour temps réel

### Performance MINIMUM
- [ ] Temps réponse API < 200ms sous charge (2000 réservations/jour)
- [ ] Support 50 utilisateurs simultanés sans conflit
- [ ] Interface tactile responsive sur tablettes
- [ ] Plans SVG fluides (pas de lag lors sélection siège)
- [ ] Cache Redis efficace (hit rate > 80%)
- [ ] 0 bugs critiques sur parcours principal
- [ ] Monitoring temps réel superviseur

## INSTRUCTIONS DE CODAGE

### Style de Code
```
- Variables en français : $trajet, $siege, $passager
- Commentaires en français
- API responses en JSON français
- Classes Laravel : CamelCase anglais (TrajetController)
```

### Structure Projet
```
Backend (Laravel):
├── app/Http/Controllers/
│   ├── TrajetController.php
│   ├── VehiculeController.php  
│   ├── VoyageController.php
│   ├── ReservationController.php
│   └── DashboardController.php
├── app/Models/
│   ├── Trajet.php
│   ├── Vehicule.php
│   ├── Voyage.php
│   └── Reservation.php
├── app/Services/
│   ├── OptimisationService.php
│   ├── ConcurrenceService.php (Redis locks)
│   └── VehiculeConfigService.php
├── database/migrations/
├── database/seeders/ (4 types véhicules)
└── routes/api.php

Frontend (Next.js):
├── pages/
│   ├── billetterie/index.js
│   ├── dashboard/supervision.js
│   └── api/websocket.js
├── components/
│   ├── vehicules/
│   │   ├── PlanMinibus15.jsx
│   │   ├── PlanBus30.jsx  
│   │   ├── PlanBus50.jsx
│   │   └── PlanBusArticule80.jsx
│   ├── SelectionTrajet.jsx
│   ├── OptimisateurSieges.jsx
│   ├── GestionConcurrence.jsx
│   └── StatsTempsReel.jsx
└── styles/vehicules/
    ├── minibus.css
    ├── bus-standard.css
    └── bus-articule.css
```

## QUESTIONS À POSER SI BLOQUÉ
1. "Dois-je implémenter tous les types de véhicules maintenant ?" → OUI, les 4 types (15/30/50/80 places)
2. "Comment gérer 50 utilisateurs simultanés ?" → Redis locks + WebSockets + Cache intelligent
3. "L'algorithme doit-il être très complexe ?" → NON, logique simple mais adaptée à chaque type véhicule
4. "Intégrations ERP nécessaires ?" → NON, Phase 1 = export CSV uniquement
5. "Dashboard supervision obligatoire ?" → OUI, monitoring temps réel des 4 types véhicules

---

## INSTRUCTION FINALE À L'AGENT
"Commence par créer la structure Laravel avec les 6 tables (incluant voyages), puis les 4 templates SVG de véhicules, puis l'interface Next.js avec gestion Redis. Priorité absolue : gestion concurrence 50 utilisateurs + performance 2000 réservations/jour. Implémenter les 4 types de véhicules dès Phase 1. Code commenté en français, dashboard supervision obligatoire. MVP robuste en 2 semaines maximum avec tests de charge."