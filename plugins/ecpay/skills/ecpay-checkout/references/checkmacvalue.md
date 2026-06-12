# CheckMacValue 演算法

綠界串接最容易出錯的就是這裡。簽不對時，收銀台會直接顯示
`CheckMacValue Error`，且錯誤訊息不會告訴你差在哪。

## 演算法步驟（EncryptType=1, SHA256）

1. 取所有參數（**排除 `CheckMacValue` 本身**），依參數名稱 A→Z
   **不分大小寫**排序
2. 組成 `key1=value1&key2=value2...` 字串
3. 最前面加 `HashKey={你的HashKey}&`，最後面加 `&HashIV={你的HashIV}`
4. 對整串做 **.NET 規格的 URL encode**（重點，見下節）
5. 轉全小寫
6. SHA256 雜湊
7. 轉全大寫 → 即為 CheckMacValue

## .NET URL encode 的陷阱

綠界規格要求 .NET `HttpUtility.UrlEncode` 的行為。各語言內建的
encode 都有出入，必須手動修正：

| 字元 | .NET 結果 | JS `encodeURIComponent` | PHP `urlencode` |
|------|-----------|------------------------|-----------------|
| 空格 | `+` | `%20` ❌ | `+` ✅ |
| `~` | `%7e` | `~` ❌ | `%7E` ✅(需轉小寫) |
| `'` | `%27` | `'` ❌ | `%27` ✅ |
| `!` | `!` | `!` ✅ | `%21` ❌ |
| `(` `)` | `(` `)` | `(` `)` ✅ | `%28` `%29` ❌ |
| `*` | `*` | `*` ✅ | `%2A` ❌ |
| `-` `_` `.` | 原樣 | 原樣 ✅ | 原樣 ✅ |

## Node.js 實作（實戰驗證）

```js
import { createHash } from 'node:crypto';

function dotNetUrlEncode(s) {
  return encodeURIComponent(s)
    .toLowerCase()
    .replace(/%20/g, '+')
    .replace(/~/g, '%7e')
    .replace(/'/g, '%27')
    .replace(/%21/g, '!')
    .replace(/%28/g, '(')
    .replace(/%29/g, ')')
    .replace(/%2a/g, '*');
}

export function checkMacValue(params, hashKey, hashIV) {
  const sorted = Object.keys(params)
    .filter((k) => k !== 'CheckMacValue')
    .sort((a, b) => (a.toLowerCase() < b.toLowerCase() ? -1 : 1));
  const raw = `HashKey=${hashKey}&`
    + sorted.map((k) => `${k}=${params[k]}`).join('&')
    + `&HashIV=${hashIV}`;
  return createHash('sha256')
    .update(dotNetUrlEncode(raw))
    .digest('hex')
    .toUpperCase();
}

export function verifyNotify(body, hashKey, hashIV) {
  if (!body.CheckMacValue) return false;
  return checkMacValue(body, hashKey, hashIV) === body.CheckMacValue;
}
```

## PHP 實作

```php
function ecpayCheckMacValue(array $params, string $hashKey, string $hashIV): string
{
    unset($params['CheckMacValue']);
    uksort($params, fn($a, $b) => strcasecmp($a, $b));

    $raw = "HashKey={$hashKey}";
    foreach ($params as $k => $v) {
        $raw .= "&{$k}={$v}";
    }
    $raw .= "&HashIV={$hashIV}";

    // PHP urlencode 已是 + 空格，但會多編碼 ! * ( )，需還原
    $encoded = strtolower(urlencode($raw));
    $encoded = str_replace(
        ['%21', '%28', '%29', '%2a'],
        ['!',   '(',   ')',   '*'],
        $encoded
    );

    return strtoupper(hash('sha256', $encoded));
}
```

## 自我驗證技巧

簽完後最快的驗證方式：直接把表單送到**測試環境**收銀台。

- 簽錯 → 立即顯示 `CheckMacValue Error` 頁
- 簽對 → 正常顯示付款方式選擇頁

不需要真的付款就能驗證簽章正確性。
