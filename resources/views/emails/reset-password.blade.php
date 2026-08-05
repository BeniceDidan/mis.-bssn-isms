<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0; padding:32px 16px; background:#eef1f5; font-family: -apple-system, 'Segoe UI', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; padding:32px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 4px; font-size:18px; color:#10161f;">BSSN ISMS</h1>
                            <p style="margin:0 0 20px; font-size:13px; color:#4a5568;">Badan Siber dan Sandi Negara</p>

                            <h2 style="margin:0 0 12px; font-size:16px; color:#10161f;">Atur Ulang Kata Sandi</h2>
                            <p style="margin:0 0 20px; font-size:14px; line-height:1.6; color:#374151;">
                                Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Klik tombol di bawah untuk membuat kata sandi baru.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:999px; background:#0284c7;">
                                        <a href="{{ $url }}" style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                                            Atur Ulang Kata Sandi
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0; font-size:12.5px; line-height:1.6; color:#9ca3af;">
                                Jika Anda tidak meminta ini, abaikan email ini — kata sandi Anda tidak akan berubah.
                            </p>
                            <p style="margin:8px 0 0; font-size:12px; color:#9ca3af; word-break:break-all;">
                                Atau salin tautan ini: {{ $url }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
