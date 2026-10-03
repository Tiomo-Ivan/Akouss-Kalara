import 'package:flutter/material.dart';

import 'screens/home_screen.dart';
import 'screens/catalog_screen.dart';
import 'screens/cart_screen.dart';
import 'screens/profile_screen.dart';

void main() {
  runApp(const AkoussKalaraApp());
}

class AkoussKalaraApp extends StatelessWidget {
  const AkoussKalaraApp({super.key});

  // ===========================================================================
  // AKOUSS KALARA — DESIGN SYSTEM
  // Palette alignée sur la version Web.
  // ===========================================================================

  static const Color marine = Color(0xFF16213E);
  static const Color marineClair = Color(0xFF1F3A5F);
  static const Color accent = Color(0xFF2255CC);
  static const Color accentHover = Color(0xFF1A44A8);

  static const Color fond = Color(0xFFF7F8FA);
  static const Color carte = Color(0xFFFFFFFF);
  static const Color texte = Color(0xFF1A1A2E);
  static const Color texteClair = Color(0xFF6B7280);
  static const Color bordure = Color(0xFFE5E7EB);

  static const Color succes = Color(0xFF1F8A3D);
  static const Color danger = Color(0xFFC0392B);
  static const Color or = Color(0xFFE0A831);

  @override
  Widget build(BuildContext context) {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: accent,
      brightness: Brightness.light,
    ).copyWith(
      primary: accent,
      onPrimary: Colors.white,
      secondary: marineClair,
      onSecondary: Colors.white,
      surface: carte,
      onSurface: texte,
      error: danger,
      onError: Colors.white,
      outline: bordure,
    );

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Akouss Kalara',
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: colorScheme,
        scaffoldBackgroundColor: fond,
        fontFamily: 'Roboto',

        // -------------------------------------------------------------------
        // AppBar
        // -------------------------------------------------------------------
        appBarTheme: const AppBarTheme(
          backgroundColor: marine,
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: false,
          titleTextStyle: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.w800,
          ),
        ),

        // -------------------------------------------------------------------
        // Cartes
        // -------------------------------------------------------------------
        cardTheme: const CardThemeData(
          color: carte,
          elevation: 0,
          margin: EdgeInsets.zero,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.all(Radius.circular(10)),
            side: BorderSide(
              color: bordure,
              width: 1,
            ),
          ),
        ),

        // -------------------------------------------------------------------
        // Boutons principaux
        // -------------------------------------------------------------------
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: accent,
            foregroundColor: Colors.white,
            disabledBackgroundColor: accent.withValues(alpha: 0.5),
            disabledForegroundColor: Colors.white,
            elevation: 0,
            padding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 12,
            ),
            shape: const RoundedRectangleBorder(
              borderRadius: BorderRadius.all(Radius.circular(6)),
            ),
            textStyle: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),

        // -------------------------------------------------------------------
        // Boutons secondaires
        // -------------------------------------------------------------------
        outlinedButtonTheme: OutlinedButtonThemeData(
          style: OutlinedButton.styleFrom(
            foregroundColor: accent,
            side: const BorderSide(
              color: accent,
              width: 1.5,
            ),
            padding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 12,
            ),
            shape: const RoundedRectangleBorder(
              borderRadius: BorderRadius.all(Radius.circular(6)),
            ),
            textStyle: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),

        // -------------------------------------------------------------------
        // Champs
        // -------------------------------------------------------------------
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: carte,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 10,
          ),
          border: const OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(6)),
            borderSide: BorderSide(
              color: bordure,
              width: 1.5,
            ),
          ),
          enabledBorder: const OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(6)),
            borderSide: BorderSide(
              color: bordure,
              width: 1.5,
            ),
          ),
          focusedBorder: const OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(6)),
            borderSide: BorderSide(
              color: accent,
              width: 1.5,
            ),
          ),
          errorBorder: const OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(6)),
            borderSide: BorderSide(
              color: danger,
              width: 1.5,
            ),
          ),
          focusedErrorBorder: const OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(6)),
            borderSide: BorderSide(
              color: danger,
              width: 1.5,
            ),
          ),
          hintStyle: const TextStyle(
            color: texteClair,
            fontSize: 14,
          ),
        ),

        // -------------------------------------------------------------------
        // Navigation mobile
        // -------------------------------------------------------------------
        navigationBarTheme: const NavigationBarThemeData(
          backgroundColor: carte,
          indicatorColor: Color(0xFFE8EEFF),
          elevation: 0,
          labelTextStyle: WidgetStatePropertyAll(
            TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),

        // -------------------------------------------------------------------
        // Chips / badges
        // -------------------------------------------------------------------
        chipTheme: ChipThemeData(
          backgroundColor: const Color(0xFFE8EEFF),
          selectedColor: accent,
          disabledColor: bordure,
          labelStyle: const TextStyle(
            color: texte,
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
          secondaryLabelStyle: const TextStyle(
            color: Colors.white,
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
          padding: const EdgeInsets.symmetric(
            horizontal: 8,
            vertical: 3,
          ),
          shape: const StadiumBorder(),
          side: BorderSide.none,
        ),
      ),
      home: const MainNavigation(),
    );
  }
}

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    HomeScreen(),
    CatalogScreen(),
    CartScreen(),
    ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Akouss Kalara'),
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Accueil',
          ),
          NavigationDestination(
            icon: Icon(Icons.menu_book_outlined),
            selectedIcon: Icon(Icons.menu_book),
            label: 'Catalogue',
          ),
          NavigationDestination(
            icon: Icon(Icons.shopping_cart_outlined),
            selectedIcon: Icon(Icons.shopping_cart),
            label: 'Panier',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
