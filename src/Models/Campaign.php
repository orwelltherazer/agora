<?php

namespace Agora\Models;

class Campaign
{
    private $id;
    private $titre;
    private $description;
    private $demandeur;
    private $demandeur_email;
    private $date_event_debut;
    private $date_event_fin;
    private $statut = 'brouillon';
    private $priorite = 'normale';
    private $campagne_source_id;
    private $created_by;
    private $created_at;
    private $updated_at;
    private $validated_at;
    private $published_at;
    private $archived_at;

    // Relations
    private $supports = [];
    private $validateurs = [];
    private $files = [];
    private $comments = [];

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    private function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function getDescription(): ?string { return $this->description; }
    public function getDemandeur(): ?string { return $this->demandeur; }
    public function getDemandeurEmail(): ?string { return $this->demandeur_email; }
    public function getDateEventDebut(): ?string { return $this->date_event_debut; }
    public function getDateEventFin(): ?string { return $this->date_event_fin; }
    public function getStatut(): string { return $this->statut; }
    public function getPriorite(): string { return $this->priorite; }
    public function getCampagneSourceId(): ?int { return $this->campagne_source_id; }
    public function getCreatedBy(): ?int { return $this->created_by; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getValidatedAt(): ?string { return $this->validated_at; }
    public function getPublishedAt(): ?string { return $this->published_at; }
    public function getArchivedAt(): ?string { return $this->archived_at; }
    public function getSupports(): array { return $this->supports; }
    public function getValidateurs(): array { return $this->validateurs; }
    public function getFiles(): array { return $this->files; }
    public function getComments(): array { return $this->comments; }

    // Setters
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function setDemandeur(string $demandeur): self { $this->demandeur = $demandeur; return $this; }
    public function setDemandeurEmail(?string $email): self { $this->demandeur_email = $email; return $this; }
    public function setDateEventDebut(string $date): self { $this->date_event_debut = $date; return $this; }
    public function setDateEventFin(?string $date): self { $this->date_event_fin = $date; return $this; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
    public function setPriorite(string $priorite): self { $this->priorite = $priorite; return $this; }
    public function setCampagneSourceId(?int $id): self { $this->campagne_source_id = $id; return $this; }
    public function setCreatedBy(int $userId): self { $this->created_by = $userId; return $this; }
    public function setCreatedAt(string $date): self { $this->created_at = $date; return $this; }
    public function setUpdatedAt(string $date): self { $this->updated_at = $date; return $this; }
    public function setValidatedAt(?string $date): self { $this->validated_at = $date; return $this; }
    public function setPublishedAt(?string $date): self { $this->published_at = $date; return $this; }
    public function setArchivedAt(?string $date): self { $this->archived_at = $date; return $this; }
    public function setSupports(array $supports): self { $this->supports = $supports; return $this; }
    public function setValidateurs(array $validateurs): self { $this->validateurs = $validateurs; return $this; }
    public function setFiles(array $files): self { $this->files = $files; return $this; }
    public function setComments(array $comments): self { $this->comments = $comments; return $this; }

    // Méthodes utilitaires
    public function getStatutLabel(): string
    {
        $labels = [
            'brouillon' => 'Brouillon',
            'en_validation' => 'En validation',
            'validee' => 'Validée',
            'publiee' => 'Publiée',
            'archivee' => 'Archivée',
            'refusee' => 'Refusée',
            'annulee' => 'Annulée',
        ];
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getStatutColor(): string
    {
        $colors = [
            'brouillon' => 'gray',
            'en_validation' => 'orange',
            'validee' => 'green',
            'publiee' => 'blue',
            'archivee' => 'gray',
            'refusee' => 'red',
            'annulee' => 'red',
        ];
        return $colors[$this->statut] ?? 'gray';
    }

    public function isPriorite(): bool
    {
        return $this->priorite === 'haute';
    }

    public function isDupliquee(): bool
    {
        return $this->campagne_source_id !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'demandeur' => $this->demandeur,
            'demandeur_email' => $this->demandeur_email,
            'date_event_debut' => $this->date_event_debut,
            'date_event_fin' => $this->date_event_fin,
            'statut' => $this->statut,
            'priorite' => $this->priorite,
            'campagne_source_id' => $this->campagne_source_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'validated_at' => $this->validated_at,
            'published_at' => $this->published_at,
            'archived_at' => $this->archived_at,
        ];
    }
}
