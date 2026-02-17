<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BankConnectionCertificate extends Model
{
    protected $table = 'bank_connection_certificates';

    protected $fillable = [
        'bank_connection_id',
        'tls_certificate', 'tls_key',
        'signing_certificate', 'signing_key',
    ];

    protected $hidden = ['tls_key', 'signing_key'];

    // Encrypt all certificate fields
    public function setTlsCertificateAttribute($value): void
    {
        $this->attributes['tls_certificate'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getTlsCertificateAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setTlsKeyAttribute($value): void
    {
        $this->attributes['tls_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getTlsKeyAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setSigningCertificateAttribute($value): void
    {
        $this->attributes['signing_certificate'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSigningCertificateAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setSigningKeyAttribute($value): void
    {
        $this->attributes['signing_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSigningKeyAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class, 'bank_connection_id');
    }
}
