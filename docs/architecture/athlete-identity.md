# Identidad de atleta — relaciones

Ver `docs/adr/0004-athlete-canonical-identity.md` para las decisiones y su
justificación. Este documento es solo el mapa de relaciones.

```
                          ┌───────────────────┐
                          │       User         │
                          │ (cuenta, login)     │
                          └─────────┬──────────┘
                        0..1 hasOne │ │ hasOne 0..1
                                    │ │
                     ┌──────────────┘ └───────────────┐
                     ▼                                 ▼
          ┌─────────────────────┐           ┌─────────────────────┐
          │   AthleteProfile    │           │       Athlete        │
          │ (perfil público,    │           │  (persona canónica,  │
          │  siempre de un User)│           │  deduplicada)        │
          └─────────────────────┘           └──────────┬───────────┘
                                                          │
                    ┌────────────────┬─────────────────┬─┴───────────────────┐
                    │ hasMany        │ hasMany         │ hasMany              │ hasMany
                    ▼                ▼                 ▼                      ▼
        ┌─────────────────────┐ ┌─────────┐   ┌──────────────┐   ┌────────────────────────┐
        │  EventParticipant   │ │  Plate  │   │    Medal      │   │ AthleteExternalIdentity │
        │  (snapshot inmutable│ │         │   │                │   │ (Slice 4 — sin provider │
        │   por evento)       │ │         │   │                │   │  real conectado aún)    │
        └──────────┬───────────┘ └─────────┘   └──────────────┘   └────────────────────────┘
                    │ hasOne
                    ▼
          ┌─────────────────────┐
          │    EventResult       │
          │  (tiempos, splits)   │
          └─────────────────────┘
```

## Puntos clave

- **`User —0..1— Athlete`**: una cuenta puede no tener identidad canónica
  todavía (nunca reclamó nada, nunca se registró con datos que resolvieran
  a un Athlete). Un `Athlete` puede no tener `User` (solo existe por datos
  de importación).
- **`Athlete —N— EventParticipant`**: el corazón de la deduplicación. La
  misma persona corriendo en 3 eventos = 1 `Athlete`, 3
  `EventParticipant`, 3 bibs distintos, 3 nombres/emails potencialmente
  distintos si el snapshot de cada evento variaba (el snapshot nunca se
  reescribe con datos "más actuales" del Athlete).
- **`EventParticipant —1— EventResult`**: sin cambios de Slice 3, cada
  participación tiene a lo más un resultado.
- **`Plate —> Athlete`** y **`Plate —> EventParticipant` (opcional)**: una
  placa "quick" (sin registro previo) tiene `Athlete` pero puede no tener
  `EventParticipant` nunca. Una placa integrada tiene ambos desde que se
  genera.
- **`AthleteExternalIdentity`**: existe el schema completo desde Slice 3
  para no requerir otra migración cuando llegue el primer proveedor real
  en Slice 4, pero hoy no hay ningún flujo que la puebla fuera de
  pruebas/factories.
