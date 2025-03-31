import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import SignIn from './pages/SignIn';
import SignUp from './pages/SignUp';
import ForgotPassword from './pages/ForgotPassword';
import Home from './pages/Home';
import Causes from './pages/Causes';
import CauseDetail from './pages/CauseDetail';
import Payment from './pages/Payment';
import PaymentSuccess from './pages/PaymentSuccess';
import PaymentError from './pages/PaymentError';
import Profile from './pages/Profile';
import Certificate from './pages/Certificate';
import ErrorBoundary from './components/ErrorBoundary';
import { Toaster } from '@/components/ui/toaster';
import TranslationProvider from './components/TranslationProvider';
import { ProtectedRoute } from './components/ProtectedRoute';
import { CentrifugoProvider } from './contexts/CentrifugoContext';

function App() {
  return (
    <ErrorBoundary>
      <TranslationProvider>
        <CentrifugoProvider>
          <Router>
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/signin" element={<SignIn />} />
              <Route path="/signup" element={<SignUp />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route path="/causes" element={<Causes />} />
              <Route path="/causes/:id" element={<CauseDetail />} />
              <Route path="/causes/:id/donate" element={
                <ProtectedRoute>
                  <Payment />
                </ProtectedRoute>
              } />
              <Route path="/payment/success" element={<PaymentSuccess />} />
              <Route path="/payment/error" element={<PaymentError />} />
              <Route path="/profile" element={
                <ProtectedRoute>
                  <Profile />
                </ProtectedRoute>
              } />
              <Route path="/certificates/:id" element={
                <ProtectedRoute>
                  <Certificate />
                </ProtectedRoute>
              } />
            </Routes>
            <Toaster />
          </Router>
        </CentrifugoProvider>
      </TranslationProvider>
    </ErrorBoundary>
  );
}

export default App;