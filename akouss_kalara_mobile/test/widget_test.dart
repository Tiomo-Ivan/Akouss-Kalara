import 'package:flutter_test/flutter_test.dart';

import 'package:akouss_kalara_mobile/main.dart';

void main() {
  testWidgets(
    'Akouss Kalara affiche la navigation principale',
    (WidgetTester tester) async {
      await tester.pumpWidget(const AkoussKalaraApp());

      expect(find.text('Akouss Kalara'), findsOneWidget);
      expect(find.text('Accueil'), findsOneWidget);
      expect(find.text('Catalogue'), findsOneWidget);
      expect(find.text('Panier'), findsOneWidget);
      expect(find.text('Profil'), findsOneWidget);
    },
  );
}
