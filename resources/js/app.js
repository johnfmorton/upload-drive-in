import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

window.Alpine = Alpine;
Alpine.plugin(persist);



// Alpine.magic('clipboard', () => {
//   return (subject) => navigator.clipboard.writeText(subject);
// });

Alpine.start();
