import axios from 'axios'

export const api = axios.create({
  baseURL: 'http://localhost/api', // Адрес нашего Laravel API
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})