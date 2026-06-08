import React from 'react';
import { createRoot } from 'react-dom/client';

function App(): React.JSX.Element {
    return <h1>Hello React + TypeScript on Laravel</h1>;
}

const el = document.getElementById('app');
if (el) {
    createRoot(el).render(<App />);
}
