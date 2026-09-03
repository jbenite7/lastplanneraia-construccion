<?php

namespace App\Security;

use Database;

class EventService
{
    private $db;
    private $tableExistsCache = [];

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    public function emit(
        string $eventCode,
        string $eventAction,
        string $description = '',
        array $context = [],
        ?string $project = null,
        string $result = 'ok',
    ): bool {
        $eventCode = strtolower(trim($eventCode));
        $eventAction = strtolower(trim($eventAction));
        $result = strtolower(trim($result));

        $mapping = $this->resolveEventMapping($eventCode, $eventAction);
        $modulo = $mapping['modulo_legacy'] ?? 'Sistema';
        $accion = $mapping['accion_legacy'] ?? strtoupper($eventAction !== '' ? $eventAction : 'EVENTO');

        $meta = [
            'event_code' => $eventCode,
            'event_action' => $eventAction,
            'event_result' => $result !== '' ? $result : 'ok',
        ];

        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                $meta['context_json'] = $json;
            }
        }

        return $this->db->logActivity($modulo, $accion, $description, $project, $meta);
    }

    public function emitAuthorizationDenied(string $permissionKey, array $context = [], ?string $project = null): bool
    {
        $payload = array_merge($context, ['permission_key' => $permissionKey]);

        return $this->emit(
            'seguridad.autorizacion',
            'denegada',
            'Intento de acceso denegado por RBAC',
            $payload,
            $project,
            'denegado',
        );
    }

    private function resolveEventMapping(string $eventCode, string $eventAction): array
    {
        $fromDb = $this->loadEventMappingFromDb($eventCode, $eventAction);
        if (!empty($fromDb)) {
            return $fromDb;
        }

        $dictionary = RbacCatalog::eventDictionary();
        if (isset($dictionary[$eventCode][$eventAction])) {
            return $dictionary[$eventCode][$eventAction];
        }

        return [];
    }

    private function loadEventMappingFromDb(string $eventCode, string $eventAction): array
    {
        if (!$this->tableExists('event_dictionary')) {
            return [];
        }

        $sql = "SELECT modulo_legacy, accion_legacy
                FROM event_dictionary
                WHERE event_code = ? AND event_action = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventCode, $eventAction]);
        $row = $stmt->fetch();

        if (!$row) {
            return [];
        }

        return [
            'modulo_legacy' => $row['modulo_legacy'] ?? null,
            'accion_legacy' => $row['accion_legacy'] ?? null,
        ];
    }

    private function tableExists(string $tableName): bool
    {
        if (isset($this->tableExistsCache[$tableName])) {
            return $this->tableExistsCache[$tableName];
        }

        // Database::tableExists() lee `information_schema` por fuera del gate de scope, que
        // rechaza las tablas calificadas por schema desde el 2026-08-29. Armar la consulta aquí
        // lanzaba DomainException, y el catch que la envolvía la convertía en «la tabla no
        // existe»: la misma falla silenciosa que dejó a un administrador con rol de
        // subcontratista en RbacService.
        $exists = $this->db->tableExists($tableName);

        $this->tableExistsCache[$tableName] = $exists;

        return $exists;
    }
}
