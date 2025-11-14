
<style scoped>
/* Container principal */
.projects-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 30px 20px;
}

/* Header avec titre et filtres */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

h1 {
  font-size: 32px;
  font-weight: bold;
  margin: 0;
  color: #000;
}

/* Boutons de filtre */
.filter-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-btn {
  background-color: #fff;
  color: #333;
  border: 2px solid #ddd;
  border-radius: 20px;
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  border-color: #FF6B5B;
  color: #FF6B5B;
}

.filter-btn.active {
  background-color: #FF6B5B;
  color: #fff;
  border-color: #FF6B5B;
}

/* Messages de feedback */
.message {
  margin-bottom: 20px;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 500;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* État de chargement */
.loading {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  color: #666;
}

/* Grille de projets (2 colonnes sur desktop) */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 30px;
  margin-top: 20px;
}

/* Responsive : 1 colonne sur mobile/tablette */
@media (max-width: 968px) {
  .projects-grid {
    grid-template-columns: 1fr;
  }
  
  .header {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* Carte individuelle de projet */
.project-card {
  background-color: #fff;
  border: 2px solid #000;
  border-radius: 20px;
  padding: 25px;
  display: flex;
  flex-direction: column;
  gap: 15px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
}

.project-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

/* Carte archivée avec style atténué */
.project-card.archived {
  opacity: 0.7;
  background-color: #f5f5f5;
}

/* Header de la carte avec badges */
.card-header {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* Badges style pill */
.badge {
  display: inline-block;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid #000;
  background-color: #fff;
}

.badge-category {
  color: #000;
}

.badge-date {
  color: #666;
}

.badge-archived {
  background-color: #e0e0e0;
  color: #555;
  border-color: #999;
}

/* Titre du projet */
.project-title {
  font-size: 22px;
  font-weight: bold;
  color: #000;
  margin: 0;
  line-height: 1.3;
}

/* Description du projet */
.project-description {
  font-size: 15px;
  color: #333;
  line-height: 1.6;
  margin: 0;
  flex-grow: 1;
}

/* Métadonnées (Rôle, Compétences) - À activer plus tard */
.project-meta {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px solid #e0e0e0;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.meta-label {
  font-size: 13px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.meta-value {
  font-size: 15px;
  color: #333;
}

.competences-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.competence-tag {
  display: inline-block;
  padding: 4px 12px;
  background-color: #f0f0f0;
  border: 1px solid #ccc;
  border-radius: 12px;
  font-size: 13px;
  color: #555;
}

/* Footer de la carte */
.card-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 10px;
}

/* Bouton Archiver (style rouge-corail comme la navigation) */
.btn-archive {
  background-color: #FF6B5B;
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 10px 24px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-archive:hover {
  background-color: #ff5545;
  transform: scale(1.05);
}

.btn-archive:active {
  transform: scale(0.98);
}

/* Message si aucun projet */
.no-projects {
  text-align: center;
  padding: 60px 20px;
}

.no-projects p {
  font-size: 18px;
  color: #666;
  font-style: italic;
  margin: 0;
}
</style>
