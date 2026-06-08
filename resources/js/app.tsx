import { createRouter, RouterProvider } from '@tanstack/react-router'
import React from 'react'
import { createRoot } from 'react-dom/client'
import { routeTree } from './routeTree.gen'
import { RootProvider } from './providers/root-provider'

const router = createRouter({ routeTree })

const el = document.getElementById('app');
if (el) {
    createRoot(el).render(
        <React.StrictMode>
            <RootProvider>
                <RouterProvider router={router}></RouterProvider>
            </RootProvider>
        </React.StrictMode>
    );
}
