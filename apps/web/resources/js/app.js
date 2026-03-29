import '../css/app.css';
import './bootstrap'
import Alpine from 'alpinejs'
import adminShell from './adminShell'
import landingCapture from './landingCapture'

window.Alpine = Alpine

document.addEventListener('alpine:init', () => {
    Alpine.data('adminShell', adminShell)
    Alpine.data('landingCapture', landingCapture)
})

Alpine.start()
