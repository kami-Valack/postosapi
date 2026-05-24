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
class Schemas
{
}
