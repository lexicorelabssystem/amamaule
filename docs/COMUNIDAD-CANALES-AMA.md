# Comunidad futura AMA - modelo de canales

La comunidad se modela como canales tem?ticos asociados opcionalmente a una disciplina art?stica. Esto permite partir con espacios simples por disciplina y crecer hacia chat, foros, moderaci?n avanzada o API p?blica sin rehacer la base.

## Entidades

- `community_channels`: espacios de conversaci?n. Pueden ser generales o vinculados a `disciplines`.
- `community_messages`: mensajes internos publicados por usuarios autenticados dentro de un canal.
- `moderation_reports`: reportes sobre contenido comunitario, inicialmente mensajes.

## Reglas base

- Los usuarios con permiso `community.view` pueden ver canales activos.
- Los usuarios con permiso `community.message` pueden enviar mensajes en canales activos.
- Los usuarios con permiso `community.moderate` pueden revisar reportes, ocultar mensajes y resolver casos.
- Un mensaje reportado no se elimina autom?ticamente; queda visible hasta que moderaci?n decida ocultarlo.
- Los mensajes ocultos no aparecen en el canal, pero se preservan para auditor?a.

## Escalabilidad

- Para chat en tiempo real se puede emitir eventos Laravel desde `CommunityMessageController`.
- Para retenci?n se puede agregar un comando que archive mensajes antiguos por canal.
- Para moderaci?n avanzada se puede ampliar `moderation_reports` con categor?as, evidencias o SLA.
- Para WordPress se exponen cat?logos p?blicos cacheados separados de la comunidad privada.
