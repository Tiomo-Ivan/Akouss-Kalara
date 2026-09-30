import 'package:flutter/material.dart';

import '../services/auth_service.dart';
import 'login_screen.dart';
import 'orders_screen.dart';
import 'register_screen.dart';
class ProfileScreen extends StatefulWidget {
const ProfileScreen({super.key});

@override
State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
Map<String, dynamic>? _user;
bool _isLoading = true;

@override
void initState() {
super.initState();
_loadUser();
}

Future<void> _loadUser() async {
final isLoggedIn = await AuthService.isLoggedIn();

if (!isLoggedIn) {
  if (!mounted) return;

  setState(() {
    _user = null;
    _isLoading = false;
  });

  return;
}

final user = await AuthService.getUser();

if (!mounted) return;

setState(() {
  _user = user;
  _isLoading = false;
});

}

Future<void> _openLogin() async {
final result = await Navigator.push(
context,
MaterialPageRoute(
builder: (context) => const LoginScreen(),
),
);

if (result == true) {
  await _loadUser();
}

}

Future<void> _logout() async {
await AuthService.logout();

if (!mounted) return;

setState(() {
  _user = null;
});

ScaffoldMessenger.of(context).showSnackBar(
  const SnackBar(
    content: Text('Vous êtes déconnecté.'),
  ),
);

}

@override
Widget build(BuildContext context) {
return SafeArea(
child: Padding(
padding: const EdgeInsets.all(20),
child: _buildContent(),
),
);
}

Widget _buildContent() {
if (_isLoading) {
return const Center(
child: CircularProgressIndicator(),
);
}

if (_user == null) {
  return _buildLoggedOutView();
}

return _buildLoggedInView();

}

Widget _buildLoggedOutView() {
return Column(
crossAxisAlignment: CrossAxisAlignment.start,
children: [
const Text(
'Mon profil',
style: TextStyle(
fontSize: 28,
fontWeight: FontWeight.bold,
),
),
const SizedBox(height: 30),
Center(
child: CircleAvatar(
radius: 45,
backgroundColor:
Theme.of(context).colorScheme.primaryContainer,
child: Icon(
Icons.person,
size: 50,
color:
Theme.of(context).colorScheme.onPrimaryContainer,
),
),
),
const SizedBox(height: 30),
SizedBox(
width: double.infinity,
child: ElevatedButton.icon(
onPressed: _openLogin,
icon: const Icon(Icons.login),
label: const Text('Se connecter'),
),
),
const SizedBox(height: 12),
SizedBox(
width: double.infinity,
child: OutlinedButton.icon(
onPressed: () async {
  final result = await Navigator.push(
    context,
    MaterialPageRoute(
      builder: (context) => const RegisterScreen(),
    ),
  );

  if (result == true && mounted) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Votre compte a été créé. '
          'Vous pouvez maintenant l’activer.',
        ),
      ),
    );
  }
},
icon: const Icon(Icons.person_add),
label: const Text('Créer un compte'),
),
),
],
);
}

Widget _buildLoggedInView() {
final nom = _user?['nom_complet']?.toString() ?? 'Utilisateur';
final email = _user?['email']?.toString() ?? '';

return Column(
  crossAxisAlignment: CrossAxisAlignment.start,
  children: [
    const Text(
      'Mon profil',
      style: TextStyle(
        fontSize: 28,
        fontWeight: FontWeight.bold,
      ),
    ),
    const SizedBox(height: 30),
    Center(
      child: CircleAvatar(
        radius: 45,
        backgroundColor:
            Theme.of(context).colorScheme.primaryContainer,
        child: Icon(
          Icons.person,
          size: 50,
          color:
              Theme.of(context).colorScheme.onPrimaryContainer,
        ),
      ),
    ),
    const SizedBox(height: 24),
    Center(
      child: Text(
        nom,
        textAlign: TextAlign.center,
        style: const TextStyle(
          fontSize: 24,
          fontWeight: FontWeight.bold,
        ),
      ),
    ),
    const SizedBox(height: 8),
    Center(
      child: Text(
        email,
        textAlign: TextAlign.center,
        style: TextStyle(
          fontSize: 16,
          color: Colors.grey.shade700,
        ),
      ),
    ),
    const SizedBox(height: 30),
    Card(
      child: ListTile(
        leading: const Icon(Icons.person_outline),
        title: const Text('Nom complet'),
        subtitle: Text(nom),
      ),
    ),
    const SizedBox(height: 8),
    Card(
      child: ListTile(
        leading: const Icon(Icons.email_outlined),
        title: const Text('Adresse email'),
        subtitle: Text(email),
      ),
    ),

    const SizedBox(height: 8),
    Card(
      child: ListTile(
        leading: const Icon(Icons.receipt_long_outlined),
        title: const Text('Mes commandes'),
        subtitle: const Text(
          'Consulter l’historique de vos commandes',
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const OrdersScreen(),
            ),
          );
        },
      ),
    ),

    const Spacer(),
    SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: _logout,
        icon: const Icon(Icons.logout),
        label: const Text('Se déconnecter'),
      ),
    ),
  ],
);


}
}