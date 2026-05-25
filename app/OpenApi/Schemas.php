<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostoSearchResponse',
    type: 'object',
    required: ['q', 'count', 'postos'],
    properties: [
        new OA\Property(property: 'q', type: 'string', example: 'shell', description: 'Termo pesquisado'),
        new OA\Property(property: 'count', type: 'integer', example: 2, description: 'Número de resultados devolvidos'),
        new OA\Property(
            property: 'postos',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PostoPublic')
        ),
    ]
)]
#[OA\Schema(
    schema: 'PostoPublicPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'postos',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PostoPublic')
        ),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 25),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
    ]
)]
#[OA\Schema(
    schema: 'UpdateGestorOperationalRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['aberto', 'fechado', 'manutencao'], example: 'aberto'),
        new OA\Property(property: 'hours_24', type: 'boolean', example: true),
        new OA\Property(
            property: 'services',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Lavagem'),
                    new OA\Property(property: 'active', type: 'boolean', example: false),
                    new OA\Property(property: 'motivo_desativacao', type: 'string', example: 'bomba avariada'),
                ]
            )
        ),
        new OA\Property(
            property: 'combustiveis',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'slug', type: 'string', example: 'gasoleo'),
                    new OA\Property(property: 'disponibilidade', type: 'string', enum: ['em_stock', 'fora_stock']),
                    new OA\Property(property: 'motivo_fora_stock', type: 'string', example: 'rutura de stock'),
                ]
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'GestorOperationalResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'post_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string'),
        new OA\Property(property: 'status', type: 'string', example: 'aberto'),
        new OA\Property(property: 'hours24', type: 'boolean'),
        new OA\Property(property: 'services', type: 'object'),
        new OA\Property(property: 'combustiveis', type: 'array', items: new OA\Items(type: 'object')),
    ]
)]
#[OA\Schema(
    schema: 'PostoPublic',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '1'),
        new OA\Property(property: 'nome', type: 'string', example: 'Posto Shell'),
        new OA\Property(property: 'endereco', type: 'string', example: 'Av. 4 de Fevereiro, 123'),
        new OA\Property(property: 'tipo', type: 'string', example: 'combustivel'),
        new OA\Property(
            property: 'coordinates',
            type: 'object',
            properties: [
                new OA\Property(property: 'latitude', type: 'number', format: 'float', example: -8.8383),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 13.2344),
            ]
        ),
        new OA\Property(property: 'preco', type: 'string', nullable: true, example: '320 Kz/L'),
        new OA\Property(property: 'precoPremium', type: 'string', nullable: true, example: '340 Kz/L'),
        new OA\Property(property: 'combustivel', type: 'string', nullable: true, example: 'Gasolina'),
        new OA\Property(
            property: 'combustiveis',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'tipo', type: 'string', example: 'gasolina'),
                    new OA\Property(property: 'nome', type: 'string', example: 'Gasolina'),
                    new OA\Property(property: 'disponibilidade', type: 'string', example: 'em_stock'),
                ]
            )
        ),
        new OA\Property(property: 'status', type: 'string', example: 'aberto'),
        new OA\Property(property: 'hours24', type: 'boolean', example: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(
            property: 'services',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['Wifi', 'Lavagem', 'Loja de Conveniência']
        ),
    ]
)]
#[OA\Schema(
    schema: 'Post',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Posto Central'),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'admin_id', type: 'integer', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'tipo', type: 'string', example: 'combustivel'),
        new OA\Property(property: 'preco', type: 'string', nullable: true),
        new OA\Property(property: 'preco_premium', type: 'string', nullable: true),
        new OA\Property(property: 'combustivel', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'aberto'),
        new OA\Property(property: 'hours_24', type: 'boolean'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'services', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'RoleCatalogEntry',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4, description: '1=Super Admin Premium, 2=Super Admin, 3=Admin, 4=Gestor'),
        new OA\Property(property: 'name', type: 'string', example: 'Gestor'),
        new OA\Property(property: 'type', type: 'string', enum: ['admin', 'gestor'], example: 'gestor'),
        new OA\Property(property: 'description', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'Role',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'name', type: 'string', example: 'Gestor'),
        new OA\Property(property: 'permissions', type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Stock',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'post_id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 42),
        new OA\Property(property: 'quantity', type: 'number', example: 150),
        new OA\Property(property: 'critical_level', type: 'number', nullable: true, example: 20),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StockHistory',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'stock_id', type: 'integer', example: 10),
        new OA\Property(property: 'old_quantity', type: 'number', nullable: true),
        new OA\Property(property: 'new_quantity', type: 'number', nullable: true),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'justificativa_ajuste', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StorePostRequest',
    required: ['name'],
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'address', type: 'string', maxLength: 1000, nullable: true),
        new OA\Property(property: 'latitude', type: 'number', nullable: true),
        new OA\Property(property: 'longitude', type: 'number', nullable: true),
        new OA\Property(property: 'admin_id', type: 'integer', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', nullable: true),
        new OA\Property(property: 'preco', type: 'string', nullable: true),
        new OA\Property(property: 'preco_premium', type: 'string', nullable: true),
        new OA\Property(property: 'combustivel', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', nullable: true),
        new OA\Property(property: 'hours_24', type: 'boolean', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(
            property: 'services',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['Wifi', 'Lavagem']
        ),
    ]
)]
#[OA\Schema(
    schema: 'UpdateStockRequest',
    required: ['quantity'],
    type: 'object',
    properties: [
        new OA\Property(property: 'quantity', type: 'number', minimum: 0, example: 150),
        new OA\Property(property: 'critical_level', type: 'number', minimum: 0, nullable: true, example: 20),
        new OA\Property(property: 'justificativa_ajuste', type: 'string', maxLength: 1000, nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StockUpdateResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Stock updated'),
        new OA\Property(property: 'stock', ref: '#/components/schemas/Stock'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Forbidden'),
        new OA\Property(property: 'code', type: 'integer', example: 403),
    ]
)]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Deleted'),
    ]
)]
#[OA\Schema(
    schema: 'PublishPriceDecreeRequest',
    required: ['preco'],
    type: 'object',
    properties: [
        new OA\Property(property: 'reference', type: 'string', example: 'ANPG/IRDP Março 2026'),
        new OA\Property(property: 'fuel_type_id', type: 'integer', example: 1, description: '1=Gasolina, 2=Gasóleo, etc.'),
        new OA\Property(property: 'preco', type: 'string', example: '320 Kz/L'),
        new OA\Property(property: 'preco_premium', type: 'string', example: '340 Kz/L'),
        new OA\Property(property: 'effective_from', type: 'string', format: 'date-time'),
        new OA\Property(property: 'confirmation_deadline_hours', type: 'integer', example: 48),
    ]
)]
#[OA\Schema(
    schema: 'ConfirmPriceDecreeRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'motivo_atraso',
            type: 'string',
            description: 'Obrigatório se a confirmação for após confirmation_deadline',
            example: 'falha de internet'
        ),
    ]
)]
#[OA\Schema(
    schema: 'StoreIncidentRequest',
    required: ['category', 'equipment_type', 'description'],
    type: 'object',
    properties: [
        new OA\Property(property: 'category', type: 'string', enum: ['urgente', 'normal'], example: 'urgente'),
        new OA\Property(
            property: 'equipment_type',
            type: 'string',
            enum: ['bomba', 'servico', 'energia', 'ev_charger', 'outro'],
            example: 'bomba'
        ),
        new OA\Property(property: 'service_id', type: 'integer', nullable: true, description: 'Obrigatório se equipment_type=servico'),
        new OA\Property(property: 'fuel_type_id', type: 'integer', nullable: true),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Bomba 2 avariada'),
        new OA\Property(property: 'description', type: 'string', example: 'Bomba de gasolina parou de responder durante abastecimento.'),
        new OA\Property(
            property: 'photos',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'binary'),
            description: 'Até 5 imagens (jpg, png, webp)'
        ),
    ]
)]
#[OA\Schema(
    schema: 'UpdateIncidentStatusRequest',
    required: ['status'],
    type: 'object',
    properties: [
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['aberto', 'em_andamento', 'resolvido', 'cancelado']
        ),
        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StorePromotionRequest',
    required: ['title', 'discount_percent', 'starts_at', 'ends_at'],
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 120, example: 'Lavagem -20%'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'discount_percent', type: 'number', format: 'float', example: 15),
        new OA\Property(property: 'service_id', type: 'integer', nullable: true),
        new OA\Property(property: 'product_id', type: 'integer', nullable: true),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'StoreCampaignRequest',
    required: ['title', 'starts_at', 'ends_at'],
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Super7 - fim de semana'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'discount_percent', type: 'number', format: 'float', example: 20),
        new OA\Property(property: 'budget_amount', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'radius_meters', type: 'integer', example: 500),
        new OA\Property(property: 'service_id', type: 'integer', nullable: true),
        new OA\Property(property: 'product_id', type: 'integer', nullable: true),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'scheduled']),
    ]
)]
#[OA\Schema(
    schema: 'UpdateCampaignRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'discount_percent', type: 'number', format: 'float'),
        new OA\Property(property: 'budget_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'radius_meters', type: 'integer'),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'CampaignFeedbackRequest',
    required: ['feedback_qualitativo'],
    type: 'object',
    properties: [
        new OA\Property(property: 'feedback_qualitativo', type: 'string', example: 'Promoção gerou muitas vendas na loja.'),
    ]
)]
#[OA\Schema(
    schema: 'TrackCampaignInteractionRequest',
    required: ['event_type', 'latitude', 'longitude'],
    type: 'object',
    properties: [
        new OA\Property(property: 'event_type', type: 'string', enum: ['view', 'click', 'conversion']),
        new OA\Property(property: 'latitude', type: 'number', format: 'float'),
        new OA\Property(property: 'longitude', type: 'number', format: 'float'),
        new OA\Property(property: 'client_user_id', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'OfflineSyncBatchRequest',
    required: ['operations'],
    type: 'object',
    properties: [
        new OA\Property(property: 'device_id', type: 'string', nullable: true),
        new OA\Property(property: 'client_batch_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(
            property: 'operations',
            type: 'array',
            items: new OA\Items(
                required: ['id', 'type', 'payload', 'client_timestamp'],
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'type', type: 'string', example: 'stock.update'),
                    new OA\Property(property: 'payload', type: 'object'),
                    new OA\Property(property: 'client_timestamp', type: 'string', format: 'date-time'),
                ],
                type: 'object'
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'UpdatePromotionRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'discount_percent', type: 'number', format: 'float'),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'IncidentResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'post_id', type: 'integer'),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'equipment_type', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'photos', type: 'array', items: new OA\Items(type: 'object')),
    ]
)]
class Schemas
{
}
