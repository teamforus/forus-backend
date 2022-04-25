## List of entities and their throttle intervals

| Entity                       | Attempts | Decay (minutes)  |
|------------------------------|----------|------------------|
| employees creation           | 10       | 10               |
| sms sending                  | 3        | 1                |
| physical card requesting     | 3        | 24 * 60          |
| notifications                | 10       | 1                |
| authorization emails sending | 10       | 10               |
| email validation             | 10       | 10               |
| product reservation creation | 100      | 180              |
| redeeming fund               | 3        | 180              |
| redirect bank connection     | 5        | 30               |
| voucher transactions         | -        | -                |

Email validation validates email for registration, format and if it's already in the system.

Voucher transactions are throttled by bunq: You can do a maximum of 3 calls per 3 second to this endpoint.