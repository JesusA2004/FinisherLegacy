# Story media — asset contract

Rendered by `resources/js/components/public/media/StoryMediaSequence.vue`,
used inside `EditorialMoment.vue` ("NO ES SOLO UNA MEDALLA").

| Archivo | Estado | Qué muestra |
|---|---|---|
| `training-dawn.mp4` | **LISTO** | Abre la escena: primer golpe audiovisual antes de pasar a las fotos. Se reproduce una vez, sin loop, al entrar en viewport. |
| `training-dawn.jpeg` | **LISTO** | Entrenamiento antes del amanecer — corredora en silueta contra el amanecer. |
| `race-effort.jpeg` | **LISTO** | Esfuerzo a mitad de carrera. |
| `finish-emotion.jpeg` | **LISTO** | Emoción/esfuerzo en plena acción. |
| `medal-closeup.jpeg` | **LISTO** | Celebración con medalla puesta. |

Los 4 `.jpeg` + el `.mp4` están referenciados directamente en el
componente (no se prueban con `useAssetExists` — ya sabemos que existen).
Orden narrativo: video → `training-dawn.jpeg` → `race-effort.jpeg` →
`finish-emotion.jpeg` → `medal-closeup.jpeg`, en loop de crossfade tras el
video. `prefers-reduced-motion` salta el video y el ciclo, mostrando
`training-dawn.jpeg` fija.
